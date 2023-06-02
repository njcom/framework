<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Sql;

use PDO;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

use function implode;

/**
 * @method prepare(string $query, array $options = []): \PDOStatement|false
 * @method beginTransaction(): bool
 * @method commit(): bool
 * @method rollBack(): bool
 * @method setAttribute(int $attribute, array|int $value): bool
 * @method errorCode(): string|null
 * @method errorInfo(): array
 * @method getAttribute(int $attribute): bool|int|string|array|null
 * @method quote(string $string, int $type = PDO::PARAM_STR): string|false
 */
abstract class Client implements IClient {
    /**
     * @var PDO|null If null then disconnected.
     */
    protected ?PDO $pdo;

    protected array $pdoConf = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_STATEMENT_CLASS    => [__NAMESPACE__ . '\\Result', []],
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    protected array $conf;

    protected string $quote;

    /**
     * DbClient constructor.
     * @param array $conf
     */
    public function __construct(array $conf) {
        $this->setConf($conf);
        $this->connect();
    }

    public function setConf(array $conf): static {
        $this->conf = $this->checkConf($conf);
        return $this;
    }

    abstract protected function checkConf(array $conf): array;

    /*    public function __destruct() {
            $this->disconnect();
        }*/

    public function conf(): array {
        return $this->conf;
    }

    public function disconnect(): void {
        $this->pdo = null;
    }

    public function isConnected(): bool {
        return null !== $this->pdo;
    }

    public function exec(string $sql): int {
        return $this->pdo->exec($sql);
    }

    public function eval(string $sql, array $args = null): Result {
        /** @var $stmt Result */
        if ($args) {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($args);
            if (false === $result) {
                throw new RuntimeException("SQL query failed, check the arguments");
            }
        } else {
            $stmt = $this->pdo->query($sql);
        }
        return $stmt;
    }

    public function lastInsertId(string $name = null): string {
        return $this->pdo->lastInsertId($name);
    }

    public function expr(mixed $expr): Expr {
        return new Expr($expr);
    }

    public function where(array|string $condition, array $args = null): array {
        $where = [];
        if (null === $args) {
            // $args not specified => $condition contains arguments
            if (is_array($condition)) {
                $where[] = implode(' AND ', $this->nameValArgs($condition));
                $args = array_values($condition);
            } else {
                $where[] = (string) $condition;
                $args = [];
            }
        } else {
            $where[] = $condition;
        }
        return ['WHERE ' . implode(' AND ', $where), $args];
    }

    public function nameValArgs(array $args): array {
        $placeholders = [];
        foreach ($args as $name => $val) {
            if (!is_string($name)) {
                throw new UnexpectedValueException();
            }
            $placeholders[] = $this->quoteIdentifier($name) . ' = ?';
        }
        return $placeholders;
    }

    public function quoteIdentifier(string|array|Expr $identifiers): string|array {
        // @see http://dev.mysql.com/doc/refman/5.7/en/identifiers.html
        $quoteIdentifier = function ($identifiers): string {
            if ($identifiers instanceof Expr) {
                return $identifiers->val();
            }
            $quoted = [];
            $parts = explode('.', $identifiers);
            $n = count($parts);
            foreach ($parts as $i => $identifier) {
                if ($identifier === '*' && $i === ($n - 1)) {
                    $quoted[] = $identifier;
                } else {
                    $quoted[] = $this->quote . $identifier . $this->quote;
                }
            }
            return implode('.', $quoted);
        };
        if (!is_array($identifiers)) {
            return $quoteIdentifier($identifiers);
        }
        $ids = [];
        foreach ($identifiers as $identifier) {
            $ids[] = $quoteIdentifier($identifier);
        }
        return $ids;
    }

    /**
     * @param callable $transaction
     * @param mixed ...$args
     * @return mixed
     * @throws Throwable
     */
    public function transaction(callable $transaction, ...$args): mixed {
        $this->pdo->beginTransaction();
        try {
            $result = $transaction($this, ...$args);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return $result;
    }

    public function inTransaction(): bool {
        return $this->pdo->inTransaction();
    }

    public function driverName(): string {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function availableDrivers(): array {
        return PDO::getAvailableDrivers();
    }

    public function __call(string $method, array $args): mixed {
        return $this->pdo->$method(...$args);
    }

    public function quoteIdentifierStr(string|array|Expr $identifiers): string {
        $result = $this->quoteIdentifier($identifiers);
        return is_array($result) ? implode(', ', $result) : $result;
    }

    public function positionalArgs(array $args): array {
        return array_fill(0, count($args), '?');
    }

    /**
     * See [SQL Syntax Allowed in Prepared Statements](https://dev.mysql.com/doc/refman/5.7/en/sql-syntax-prepared-statements.html#idm139630090954512)
     * @param callable $fn
     * @return mixed
     */
    public function usingEmulatedPrepares(callable $fn): mixed {
        $emulatePrepares = $this->getAttribute(PDO::ATTR_EMULATE_PREPARES);
        if (!$emulatePrepares) {
            $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
            try {
                $result = $fn($this);
            } finally {
                $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, intval($emulatePrepares));
            }
        } else {
            $result = $fn($this);
        }
        return $result;
    }
}