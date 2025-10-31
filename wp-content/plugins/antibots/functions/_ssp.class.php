<?php
/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2020 www.BillMinozzi.com
 * @ Modified time: 2020-11-02 10:46:16
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! current_user_can( 'administrator' ) ) {
    return;
}

/*
CREATE TABLE `wp_antibots_visitorslog` (
	`id` mediumint(9) NOT NULL,
	`access` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
	`date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
	`ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
	`human` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
	`response` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
	`method` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
	`url` text COLLATE utf8mb4_unicode_ci NOT NULL,
	`referer` text COLLATE utf8mb4_unicode_ci NOT NULL,
	`ua` text COLLATE utf8mb4_unicode_ci NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  */
class ANTIBOT_SSP
{
	/**
	 * Create the data output array for the DataTables rows
	 *
	 *  @param  array $columns Column information array
	 *  @param  array $data    Data from the SQL get
	 *  @return array          Formatted data in a row based format
	 */
	static function data_output( $columns, $data ) {
		$out = array();
		for ( $i = 0, $ien = count( $data ); $i < $ien; $i++ ) {
			$row = array();
			for ( $j = 0, $jen = count( $columns ); $j < $jen; $j++ ) {
				$column = $columns[ $j ];
				// Is there a formatter?
				if ( isset( $column['formatter'] ) ) {
					$row[ $column['dt'] ] = $column['formatter']( $data[ $i ][ $column['db'] ], $data[ $i ] );
				} else {
					$row[ $column['dt'] ] = $data[ $i ][ $columns[ $j ]['db'] ];
				}
			}
			$out[] = $row;
		}
		return $out;
	}
	/**
	 * Paging
	 *
	 * Construct the LIMIT clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @return string SQL limit clause
	 */
	static function limit( $request, $columns ) {
		$limit = '';
		if ( isset( $request['start'] ) && $request['length'] != -1 ) {
			$limit = intval( $request['start'] ) . ', ' . intval( $request['length'] );
		}
		return $limit;
	}
	/**
	 * Ordering
	 *
	 * Construct the ORDER BY clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @return string SQL order by clause
	 */
	static function order( $request, $columns ) {
		$order = '';
		if ( isset( $request['order'] ) && count( $request['order'] ) ) {
			$orderBy   = array();
			$dtColumns = self::pluck( $columns, 'dt' );
			for ( $i = 0, $ien = count( $request['order'] ); $i < $ien; $i++ ) {
				// Convert the column index into the column data property
				$columnIdx     = intval( $request['order'][ $i ]['column'] );
				$requestColumn = $request['columns'][ $columnIdx ];
				$columnIdx     = array_search( $requestColumn['data'], $dtColumns );
				$column        = $columns[ $columnIdx ];
				if ( $requestColumn['orderable'] == 'true' ) {
					$dir = sanitize_text_field( $request['order'][ $i ]['dir'] ) === 'asc' ?
						'ASC' :
						'DESC';
					// Sanitize
					if ( trim( strlen( $column['db'] ) ) < 11 ) {
						$orderBy[] = '`' . $column['db'] . '` ' . $dir;
					}
				}
			}
			if ( count( $orderBy ) ) {
				// $order = 'ORDER BY ' . implode(', ', $orderBy);
				 $order = implode( $orderBy );
				// error_log($order);
			}
		}
		return $order;
	}
	/**
	 * Perform the SQL queries needed for an server-side processing requested,
	 * utilising the helper functions of this class, limit(), order() and
	 * filter() among others. The returned array is ready to be encoded as JSON
	 * in response to an SSP request, or can be modified if needed before
	 * sending back to the client.
	 *
	 *  @param  array  $request Data sent to server by DataTables
	 *  @param  string $table SQL table to query
	 *  @param  string $primaryKey Primary key of the table
	 *  @param  array  $columns Column information array
	 *  @return array          Server-side processing response array
	 */
	static function simple( $request, $table, $primaryKey, $columns ) {
		global $wpdb;
		$str   = trim( $request['search']['value'] );
		$limit = self::limit( $request, $columns );
		$order = self::order( $request, $columns );
		$orderfull = trim( str_replace( '`', '', $order ) );
		$pos   = strpos( $orderfull, ' ' );
		$order = substr( $orderfull, 0, $pos );
		// sanitize
		//if ( strlen( $order > 11 ) ) {
		if ( strlen( $order ) > 11 ) {
			$order = 'date';
		}
		$orderDirection = sanitize_sql_orderby( substr( $orderfull, $pos + 1 ) );
		if ( sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) == '161.230.34.178' ) {
			   $access = '1';
		} else {
			$access = 'OK';
		}
		// Main query to actually get the data
		if ( empty( $str ) ) {
			$data = $wpdb->get_results(
				"
				SELECT * FROM $table
				ORDER BY $order $orderDirection
				LIMIT $limit
				",
				ARRAY_A
			);
		} else {
				$query = "
				SELECT * FROM " . $table . "
				WHERE 
					(date LIKE '%" . $str . "%' OR
					access LIKE '%" . $str . "%' OR
					referer LIKE '%" . $str . "%' OR
					url LIKE '%" . $str . "%' OR
					ua LIKE '%" . $str . "%' OR 
					method LIKE '%" . $str . "%' OR
					response LIKE '%" . $str . "%' OR 
					ip LIKE '%" . $str . "%' OR 
					human LIKE '%" . $str . "%')
				ORDER BY " . $order . " " . $orderDirection . "
				LIMIT " . $limit;
			$data = $wpdb->get_results($query, ARRAY_A);
		}
		$recordsFiltered = $wpdb->get_var(
			$wpdb->prepare(
				"
		SELECT   COUNT(*)  FROM `$table` 
		WHERE 
			(
			date like %s or
			access like %s or
			referer like %s or
			url like %s or
			ua like %s or 
			method like %s or
			response like %s or 
			ip like %s) and 
			access NOT LIKE %s
					",
				'%' . $str . '%',
				'%' . $str . '%',
				'%' . $str . '%',
				'%' . $str . '%',
				'%' . $str . '%',
				'%' . $str . '%',
				'%' . $str . '%',
				'%' . $str . '%',
				'%' . $access . '%'
			)
		);
		$recordsTotal = $wpdb->get_var(
			$wpdb->prepare("
		SELECT  COUNT(%s)  FROM `$table` WHERE access NOT LIKE %s",
				$primaryKey,
				'%' . $access . '%'
			)
		);
		/*
		 * Output
		 */
		return array(
			'draw'            => isset( $request['draw'] ) ?
				intval( $request['draw'] ) :
				0,
			'recordsTotal'    => intval( $recordsTotal ),
			'recordsFiltered' => intval( $recordsFiltered ),
			'data'            => self::data_output( $columns, $data ),
		);
	}
	/*
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Internal methods
	 */
	/**
	 * Throw a fatal error.
	 *
	 * This writes out an error message in a JSON string which DataTables will
	 * see and show to the user in the browser.
	 *
	 * @param  string $msg Message to send to the client
	 */
	static function fatal( $msg ) {
		echo json_encode(
			array(
				'error' => esc_attr( $msg ),
			)
		);
		exit( 0 );
	}
	/**
	 * Pull a particular property from each assoc. array in a numeric array,
	 * returning and array of the property values from each item.
	 *
	 *  @param  array  $a    Array to get data from
	 *  @param  string $prop Property to read
	 *  @return array        Array of property values
	 */
	static function pluck( $a, $prop ) {
		$out = array();
		for ( $i = 0, $len = count( $a ); $i < $len; $i++ ) {
			$out[] = $a[ $i ][ $prop ];
		}
		return $out;
	}
	/**
	 * Return a string from an array or a string
	 *
	 * @param  array|string $a Array to join
	 * @param  string       $join Glue for the concatenation
	 * @return string Joined string
	 */
	static function _flatten( $a, $join = ' AND ' ) {
		if ( ! $a ) {
			return '';
		} elseif ( $a && is_array( $a ) ) {
			return implode( $join, $a );
		}
		return $a;
	}
}
