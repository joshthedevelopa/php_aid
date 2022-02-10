<?php

class Database
{
    protected static array $operators = [">", "<", "=", "<=", ">="];
    protected static array $conjuctions = ["AND", "OR"];
    protected string $table = "";

    private static PDO $_pdo;
    private PDOStatement $_query;
    private int|null $_id;
    private bool $_error = false;
    private array $_results = [];
    private int $_count = 0;


    public function __construct(string $table)
    {
        $this->table = $table;
        if (!isset(self::$_pdo)) {
            try {
                self::$_pdo = new PDO(
                    dsn: "mysql:host=" . Config::get("DB_HOST") . ";dbname=" . Config::get("DB_DATABASE"),
                    username: Config::get("DB_USERNAME"),
                    password: Config::get("DB_PASSWORD")
                );
            } catch (Throwable $error) {
                die($error);
            }
        }
    }

    public function query(
        string $sql,
        array $params,
        int $flag = PDO::FETCH_OBJ
    ): Database {
        if ($this->_query = $this->_pdo->prepare($sql . " ORDER BY id DESC")) {
            foreach ($params as $key => $value) {
                $this->_query->bindValue($key + 1, $value);
            }

            try {
                if ($this->_query->execute()) {
                    $this->_query->fetchAll(mode: $flag);
                    $this->_query->rowCount();

                    if ($_id = $this->_pdo->lastInsertId()) {
                        $this->_id = intval($_id);
                    }

                    $this->_error = false;
                    return $this;
                }
            } catch (Throwable $error) {
                $this->_error = true;
            }
        }

        $this->_error = true;
        return $this;
    }

    public function _selector(
        array|int|null $targets,
        string &$suffix,
        array &$values,
        array $conjuctions
    ): bool {
        if (is_int($targets)) {
            $targets = [
                [
                    "id",
                    "=",
                    $targets
                ]
            ];
        } else if (is_null($targets)) {
            $targets = [];
        }

        if (count($targets) > 0) {
            if (is_string($targets[0])) {
                $targets = [$targets];
            }
            if (is_array($targets[0])) {
                foreach ($targets as $key => $value) {

                    if (is_array($value)) {
                        if (count($value) === 3) {

                            $name = $value[0];
                            $operator = $value[1];
                            $value = $value[2];

                            if (in_array($operator, self::$operators)) {
                                if ($suffix == "") {
                                    $suffix .= "WHERE";
                                } else {
                                    $conjuction = $conjuctions[$key] ?? self::$conjuctions[0];

                                    if (!in_array($conjuction, self::$conjuctions)) {
                                        $conjuction = self::$conjuctions[0];
                                    }
                                    $suffix .= " $conjuction ";
                                }

                                $suffix .= "$name $operator ?";
                                array_push($values, $value);
                            }
                        }
                    }
                }
                return true;
            }
        }
        return false;
    }

    public function select(
        array|int|null $targets = null,
        array|null $conjuctions = null
    ): Database {
        if ($this->_selector(
            targets: $targets,
            suffix: $suffix,
            values: $values,
            conjuctions: $conjuctions
        )) {
            return $this->query("SELECT * FROM {$this->table} $suffix", $values);
        }

        $this->_error = true;
        return $this;
    }

    public function delete(
        array|int|null $targets = null,
        array|null $conjuctions = null
    ): Database {
        if ($this->_selector(
            targets: $targets,
            suffix: $suffix,
            values: $values,
            conjuctions: $conjuctions
        )) {
            return $this->query("DELETE FROM {$this->table} $suffix", $values);
        }

        $this->_error = true;
        return $this;
    }


    public function insert(
        array $records,
    ): Database {
        $names = array_keys($records);
        $values = array_values($records);
        if (count($names) > 0) {
            $names_str = implode(", ", $names);
            $values_str = implode(", ",  array_fill(0, count($names), "?") );

            return $this->query("INSERT INTO {$this->table} ($names_str) VALUES ($values_str)", $values);
        }

        $this->_error = true;
        return $this;
    }

    public function update(
        array $records,
        array|int|null $targets = null,
        array|null $conjuctions = null
    ): Database {
        $names = array_keys($records);
        $values = array_values($records);

        if ($this->_selector(
            targets: $targets,
            suffix: $suffix,
            values: $values,
            conjuctions: $conjuctions
        )) {
            $set_str = implode(" = ?, ", $names);
            return $this->query("UPDATE {$this->table} SET $set_str $suffix", $values);
        }

        $this->_error = true;
        return $this;
    }

    public function results(int $index = null) : mixed {
        if(!is_null($index)) {
            if($this->_count > abs($index)) {
                if($index < 0) {
                    return $this->_results[$this->_count + $index];
                }

                return $this->_results[$index];
            }
        }
        return $this->_results;
    }

    public function count() : int {
        return $this->_count;
    }

    public function error() : bool {
        return $this->_error;
    }

    public function last_id() : bool {
        return $this->_id;
    }

}
