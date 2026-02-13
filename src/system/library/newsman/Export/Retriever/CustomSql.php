<?php

namespace Newsman\Export\Retriever;

use PHPSQLParser\PHPSQLParser;

/**
 * Class Export Retriever Custom SQL
 *
 * Executes SELECT-only SQL queries with OpenCart table prefix placeholder replacement.
 * Table names use {table_name} syntax, e.g. {customer} becomes oc_customer.
 *
 * @class \Newsman\Export\Retriever\CustomSql
 */
class CustomSql extends AbstractRetriever implements RetrieverInterface {

	/**
	 * Statement types that are not allowed.
	 *
	 * @var array
	 */
	protected $disallowed_statements = array(
		// DML (write).
		'INSERT',
		'UPDATE',
		'DELETE',
		'REPLACE',
		// DDL.
		'CREATE',
		'ALTER',
		'DROP',
		'TRUNCATE',
		'RENAME',
		// Privileges.
		'GRANT',
		'REVOKE',
		// Locking.
		'LOCK',
		'UNLOCK',
		// Stored procedures / dynamic SQL.
		'CALL',
		'EXECUTE',
		'PREPARE',
		'DEALLOCATE',
		// File and handler operations.
		'LOAD',
		'HANDLER',
		// Server administration.
		'SET',
		'DO',
		'FLUSH',
		'RESET',
		'PURGE',
		'KILL',
		'SHUTDOWN',
		'INSTALL',
		'UNINSTALL',
		// Table maintenance.
		'ANALYZE',
		'CHECK',
		'CHECKSUM',
		'OPTIMIZE',
		'REPAIR',
		// Schema disclosure / database switching.
		'SHOW',
		'DESCRIBE',
		'EXPLAIN',
		'USE',
		// Transaction control.
		'BEGIN',
		'COMMIT',
		'ROLLBACK',
		'SAVEPOINT',
		'RELEASE',
		'XA',
	);

	/**
	 * Process custom SQL retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 * @throws \Exception Throws exception on invalid input.
	 */
	public function process($data = array(), $store_id = null) {
		$sql = isset($data['sql']) ? trim((string)$data['sql']) : '';

		if (empty($sql)) {
			throw new \Exception('The "sql" parameter is required.');
		}

		$this->validateSelectOnly($sql);

		$sql = $this->replaceTablePlaceholders($sql);

		$this->logger->notice(
			sprintf(
				'Custom SQL export, store ID %s - Query: %s',
				$store_id,
				$sql
			)
		);

		$result = $this->registry->db->query($sql);

		$this->logger->notice(
			sprintf(
				'Custom SQL export, store ID %s - Rows returned: %d',
				$store_id,
				count($result->rows)
			)
		);

		return $result->rows;
	}

	/**
	 * Validate that the SQL is a SELECT-only query.
	 *
	 * @param string $sql SQL query.
	 *
	 * @return void
	 * @throws \Exception Throws exception if query is not SELECT-only.
	 */
	protected function validateSelectOnly($sql) {
		$this->validateNoMultipleStatements($sql);

		$parser = new PHPSQLParser();
		$parsed = $parser->parse($sql);

		if (empty($parsed)) {
			throw new \Exception('Unable to parse the SQL query.');
		}

		$statement_type = key($parsed);

		if ('SELECT' !== $statement_type) {
			throw new \Exception('Only SELECT queries are allowed. Got: ' . $statement_type);
		}

		if (isset($parsed['INTO'])) {
			throw new \Exception('SELECT INTO is not allowed.');
		}
	}

	/**
	 * Check for semicolons outside of string literals.
	 *
	 * @param string $sql SQL query.
	 *
	 * @return void
	 * @throws \Exception Throws exception if multiple statements detected.
	 */
	protected function validateNoMultipleStatements($sql) {
		$stripped = preg_replace("/'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'/s", '', $sql);
		$stripped = preg_replace('/"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"/s', '', $stripped);

		if (strpos($stripped, ';') !== false) {
			throw new \Exception('Multiple statements are not allowed.');
		}
	}

	/**
	 * Replace {table_name} placeholders with prefixed table names.
	 *
	 * @param string $sql SQL query with placeholders.
	 *
	 * @return string SQL with resolved table names.
	 */
	protected function replaceTablePlaceholders($sql) {
		return preg_replace_callback(
			'/\{([a-zA-Z0-9_]+)\}/',
			function ($matches) {
				return DB_PREFIX . $matches[1];
			},
			$sql
		);
	}
}
