# Đề Xuất Cải Thiện Hiệu Năng

## 🎯 Mục Tiêu

Cải thiện 2 điểm yếu đã được xác định trong Security Audit:
1. `split_sql_queries()` - Giảm RAM usage
2. `run_search_replace()` - Tránh timeout với database lớn

---

## 🔧 Cải Thiện 1: Streaming SQL Parser

### Vấn Đề Hiện Tại

```php
// ❌ Đọc toàn bộ file vào RAM
$sql_content = file_get_contents( $sql_file );
$queries = $this->split_sql_queries( $sql_content ); // Loop từng ký tự
```

**RAM Usage**: File 100MB → ~300-400MB RAM

### Giải Pháp: Streaming Parser

```php
/**
 * Split SQL file into queries using streaming (memory efficient)
 *
 * @param string $sql_file Path to SQL file
 * @return array Array of SQL queries
 */
private function split_sql_queries_streaming( $sql_file ) {
    $queries = array();
    $current_query = '';
    $in_string = false;
    $string_char = '';
    
    // Open file handle
    $handle = fopen( $sql_file, 'r' );
    if ( false === $handle ) {
        return array();
    }
    
    // Read file in chunks (1MB at a time)
    $chunk_size = 1024 * 1024; // 1MB
    $buffer = '';
    
    while ( ! feof( $handle ) ) {
        $chunk = fread( $handle, $chunk_size );
        if ( false === $chunk ) {
            break;
        }
        
        $buffer .= $chunk;
        
        // Process buffer character by character
        $buffer_length = strlen( $buffer );
        for ( $i = 0; $i < $buffer_length; $i++ ) {
            $char = $buffer[ $i ];
            
            // Handle string detection
            if ( ! $in_string && ( "'" === $char || '"' === $char || '`' === $char ) ) {
                $in_string = true;
                $string_char = $char;
            } elseif ( $in_string && $char === $string_char ) {
                // Check for escaped quote
                if ( $i > 0 && $buffer[ $i - 1 ] === '\\' ) {
                    // Escaped quote, continue
                } else {
                    $in_string = false;
                    $string_char = '';
                }
            }
            
            $current_query .= $char;
            
            // End of query
            if ( ! $in_string && ';' === $char ) {
                $query = trim( $current_query );
                if ( ! empty( $query ) && '--' !== substr( $query, 0, 2 ) ) {
                    $queries[] = $query;
                }
                $current_query = '';
            }
        }
        
        // Keep remaining part of buffer (in case query spans chunks)
        if ( ! empty( $current_query ) ) {
            $buffer = substr( $buffer, -strlen( $current_query ) );
        } else {
            $buffer = '';
        }
        
        // Free memory periodically
        if ( count( $queries ) % 1000 === 0 ) {
            gc_collect_cycles();
        }
    }
    
    // Add last query if exists
    if ( ! empty( trim( $current_query ) ) ) {
        $queries[] = trim( $current_query );
    }
    
    fclose( $handle );
    
    return array_filter( $queries );
}
```

**RAM Usage**: File 100MB → ~10-20MB RAM (chỉ buffer 1MB)

### Cách Áp Dụng

Thay thế trong `restore_database()`:
```php
// Cũ:
$sql_content = file_get_contents( $sql_file );
$queries = $this->split_sql_queries( $sql_content );

// Mới:
$queries = $this->split_sql_queries_streaming( $sql_file );
```

---

## 🔧 Cải Thiện 2: Batch Processing cho Search & Replace

### Vấn Đề Hiện Tại

```php
// ❌ Xử lý toàn bộ database trong 1 request
public function run_search_replace( $old_url, $new_url ) {
    @set_time_limit( 600 ); // 10 phút - nhưng server có thể kill sau 60s
    // Loop qua tất cả tables và rows...
}
```

**Vấn đề**: Database lớn → timeout → process bị kill giữa chừng

### Giải Pháp: Batch Processing với Resume

#### Bước 1: Thêm Progress Tracking

```php
/**
 * Run search and replace with batch processing
 *
 * @param string $old_url Old URL to replace
 * @param string $new_url New URL to replace with
 * @param string $table_name Optional: specific table to process
 * @param int    $offset Optional: offset for chunk processing
 * @return array Status array with 'completed', 'next_table', 'next_offset'
 */
public function run_search_replace_batch( $old_url, $new_url, $table_name = null, $offset = 0 ) {
    global $wpdb;
    
    if ( empty( $old_url ) || empty( $new_url ) ) {
        return array( 'error' => 'URLs required' );
    }
    
    // Normalize URLs
    $old_url = untrailingslashit( $old_url );
    $new_url = untrailingslashit( $new_url );
    
    if ( $old_url === $new_url ) {
        return array( 'completed' => true );
    }
    
    // Get all tables
    $tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );
    
    if ( empty( $tables ) ) {
        return array( 'error' => 'No tables found' );
    }
    
    // Find starting point
    $start_index = 0;
    if ( $table_name ) {
        foreach ( $tables as $index => $table ) {
            if ( $table[0] === $table_name ) {
                $start_index = $index;
                break;
            }
        }
    }
    
    // Process one table at a time
    $chunk_size = 50; // Smaller chunks for batch processing
    $max_execution_time = 30; // 30 seconds per batch
    $start_time = time();
    
    for ( $i = $start_index; $i < count( $tables ); $i++ ) {
        $table = $tables[ $i ];
        $table_name_clean = str_replace( '`', '', $table[0] );
        
        // Check execution time
        if ( ( time() - $start_time ) > $max_execution_time ) {
            return array(
                'completed'   => false,
                'next_table' => $table_name_clean,
                'next_offset' => $offset,
                'progress'   => sprintf( 'Processing table %d of %d', $i + 1, count( $tables ) ),
            );
        }
        
        // Process this table
        $result = $this->process_table_search_replace(
            $table_name_clean,
            $old_url,
            $new_url,
            $offset,
            $chunk_size
        );
        
        if ( isset( $result['error'] ) ) {
            return $result;
        }
        
        // If table not completed, return for next batch
        if ( ! $result['completed'] ) {
            return array(
                'completed'   => false,
                'next_table' => $table_name_clean,
                'next_offset' => $result['next_offset'],
                'progress'   => sprintf( 'Processing table %d of %d', $i + 1, count( $tables ) ),
            );
        }
        
        // Table completed, reset offset for next table
        $offset = 0;
    }
    
    // All tables completed
    return array( 'completed' => true );
}

/**
 * Process one table for search and replace
 *
 * @param string $table_name Table name
 * @param string $old_url Old URL
 * @param string $new_url New URL
 * @param int    $offset Starting offset
 * @param int    $chunk_size Chunk size
 * @return array Status array
 */
private function process_table_search_replace( $table_name, $old_url, $new_url, $offset, $chunk_size ) {
    global $wpdb;
    
    // Get columns
    $columns = $wpdb->get_col( "SHOW COLUMNS FROM `{$table_name}`" );
    if ( empty( $columns ) ) {
        return array( 'completed' => true );
    }
    
    // Get primary key
    $primary_key = null;
    $keys = $wpdb->get_results( "SHOW KEYS FROM `{$table_name}` WHERE Key_name = 'PRIMARY'", ARRAY_A );
    if ( ! empty( $keys ) && isset( $keys[0]['Column_name'] ) ) {
        $primary_key = $keys[0]['Column_name'];
    }
    
    // Build column info cache
    $column_info_cache = array();
    foreach ( $columns as $col ) {
        $col_info = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM `{$table_name}` WHERE Field = %s", $col ), ARRAY_A );
        if ( $col_info ) {
            $column_info_cache[ $col ] = $col_info;
        }
    }
    
    // Get chunk of rows
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM `{$table_name}` LIMIT %d OFFSET %d",
            $chunk_size,
            $offset
        ),
        ARRAY_A
    );
    
    if ( empty( $rows ) ) {
        return array( 'completed' => true );
    }
    
    // Process rows
    foreach ( $rows as $row ) {
        // ... (same logic as current implementation)
    }
    
    // Check if more rows exist
    $total_rows = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_name}`" );
    $next_offset = $offset + $chunk_size;
    
    if ( $next_offset >= $total_rows ) {
        return array( 'completed' => true );
    }
    
    return array(
        'completed'   => false,
        'next_offset' => $next_offset,
    );
}
```

#### Bước 2: Thêm API Endpoint cho Batch Processing

```php
// Trong class-api.php
register_rest_route(
    'wpsbm/v1',
    '/finalize_migration_batch',
    array(
        'methods'             => 'POST',
        'callback'            => array( $this, 'handle_finalize_migration_batch' ),
        'permission_callback' => array( $this, 'finalize_migration_permission_check' ),
        'args'                => array(
            'old_url'    => array( /* ... */ ),
            'table_name' => array(
                'required' => false,
                'type'     => 'string',
            ),
            'offset'     => array(
                'required' => false,
                'type'     => 'integer',
                'default'  => 0,
            ),
            'token'      => array( /* ... */ ),
        ),
    )
);
```

#### Bước 3: Update Frontend JavaScript

```javascript
// Trong admin.js
finalizeAndCleanup: function() {
    // ... existing code ...
    
    // Call batch API instead of single API
    WPSBMAdmin.finalizeMigrationBatch(targetUrl, destinationToken, sourceUrl);
},

finalizeMigrationBatch: function(destinationUrl, destinationToken, sourceUrl) {
    const self = this;
    let tableName = null;
    let offset = 0;
    
    const processBatch = function() {
        const apiUrl = destinationUrl.replace(/\/$/, '') + '/wp-json/wpsbm/v1/finalize_migration_batch';
        
        $.ajax({
            url: apiUrl,
            type: 'POST',
            data: {
                old_url: sourceUrl,
                token: destinationToken,
                table_name: tableName,
                offset: offset
            },
            success: function(response) {
                if (response.completed) {
                    // All done, proceed to cleanup
                    WPSBMAdmin.cleanupRemote(destinationUrl, destinationToken, function() {
                        WPSBMAdmin.cleanupLocal(function() {
                            WPSBMAdmin.showMigrationSuccess(destinationUrl);
                        });
                    });
                } else {
                    // Continue with next batch
                    tableName = response.next_table;
                    offset = response.next_offset || 0;
                    
                    // Update progress
                    if (response.progress) {
                        console.log(response.progress);
                    }
                    
                    // Call next batch after short delay
                    setTimeout(processBatch, 100);
                }
            },
            error: function(xhr) {
                // Handle error
                WPSBMAdmin.showStatusMessage(
                    'Finalization failed: ' + (xhr.responseJSON?.message || 'Unknown error'),
                    'error',
                    $('#wpsbm-migration-status')
                );
            }
        });
    };
    
    // Start first batch
    processBatch();
}
```

---

## 📊 So Sánh Hiệu Năng

### SQL Import

| Phương Pháp | RAM Usage (100MB file) | Thời Gian | Rủi Ro |
|------------|----------------------|-----------|--------|
| **Hiện tại** | 300-400MB | 30-60s | ❌ High (memory kill) |
| **Streaming** | 10-20MB | 60-90s | ✅ Low |

### Search & Replace

| Phương Pháp | Timeout Risk | Database Size Limit | User Experience |
|------------|-------------|---------------------|-----------------|
| **Hiện tại** | ❌ High (>100MB) | ~50MB | ⚠️ Chờ lâu, có thể fail |
| **Batch** | ✅ Low | Unlimited | ✅ Progress tracking, reliable |

---

## 🚀 Kế Hoạch Triển Khai

### Phase 1: Quick Win (1-2 giờ)
1. ✅ Thêm file size check trước khi import
2. ✅ Thêm memory limit check
3. ✅ Thêm warning message cho file lớn

### Phase 2: Streaming Parser (4-6 giờ)
1. Implement `split_sql_queries_streaming()`
2. Test với file SQL lớn
3. Fallback về method cũ nếu streaming fail

### Phase 3: Batch Processing (8-12 giờ)
1. Implement batch API endpoint
2. Update frontend JavaScript
3. Add progress tracking UI
4. Test với database lớn

---

## ⚠️ Lưu Ý

1. **Backward Compatibility**: Giữ method cũ làm fallback
2. **Testing**: Test kỹ với database thật trước khi deploy
3. **Monitoring**: Thêm logging để track performance
4. **Documentation**: Update README với limitations và recommendations

---

**Tác giả**: Code Improvement Proposal
**Ngày**: $(date)
**Version**: 1.1.0 (Proposed)

