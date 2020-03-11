<?php

declare(strict_types=1);

/*
 * This file is part of the Jejik\MT940 library
 *
 * Copyright (c) 2012 Sander Marechal <s.marechal@jejik.com>
 * Licensed under the MIT license
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */

namespace Jejik\MT940;

/**
 * Read and parse MT940 documents
 *
 * @author Sander Marechal <s.marechal@jejik.com>
 */
class Reader
{
    // Properties {{{

    /**
     * @var array A class map of bank parsers
     */
    private $parsers = array();

    /**
     * @var array All the parsers shipped in this package
     */
    private $defaultParsers = array(
        'ABN-AMRO'    => 'Jejik\MT940\Parser\AbnAmro',
        'ING'         => 'Jejik\MT940\Parser\Ing',
        'Knab'        => 'Jejik\MT940\Parser\Knab',
        'PostFinance' => 'Jejik\MT940\Parser\PostFinance',
        'Rabobank'    => 'Jejik\MT940\Parser\Rabobank',
        'Sns'         => 'Jejik\MT940\Parser\Sns',
        'Triodos'     => 'Jejik\MT940\Parser\Triodos'
    );

    /**
     * @see setStatementClass()
     * @var string|callable
     */
    private $statementClass = 'Jejik\MT940\Statement';

    /**
     * @see setAccountClass()
     * @var string|callable
     */
    private $accountClass = 'Jejik\MT940\Account';

    /**
     * @see setContraAccountClass()
     * @var string|callable
     */
    private $contraAccountClass = 'Jejik\MT940\Account';

    /**
     * @see setTransactionClass()
     * @var string|callable
     */
    private $transactionClass = 'Jejik\MT940\Transaction';

    /**
     * @see setOpeningBalanceClass()
     * @var string|callable
     */
    private $openingBalanceClass = 'Jejik\MT940\Balance';

    /**
     * @see setClosingBalanceClass()
     * @var string|callable
     */
    private $closingBalanceClass = 'Jejik\MT940\Balance';

    // }}}

    // Parser management {{{

    /**
     * Get a list of default parsers shippen in this package
     */
    public function getDefaultParsers(): array
    {
        return $this->defaultParsers;
    }

    /**
     * Get the current list of parsers
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }

    /**
     * Add a parser type to the list of parsers
     *
     * Some parsers can conflict with each other so order is important. Use
     * the $before parameter in insert a parser in a specific place.
     *
     * @param string $name Name of the parser
     * @param mixed $class Classname of the parser
     * @param mixed $before Insert the new parser before this parser
     * @throws \RuntimeException if the $before parser does not exist
     */
    public function addParser(string $name, $class, $before = null): self
    {
        if ($before === null) {
            $this->parsers[$name] = $class;
            return $this;
        }

        if (($offset = array_search($before, array_keys($this->parsers))) !== false) {
            $this->parsers = array_slice($this->parsers, 0, $offset, true)
                + array($name => $class)
                + array_slice($this->parsers, $offset, null, true);

            return $this;
        }

        throw new \RuntimeException(sprintf('Parser "%s" does not exist.', $before));
    }

    /**
     * Add multiple parsers in one step
     *
     * @param array $parsers Associative array of parser names and classes
     */
    public function addParsers(array $parsers): self
    {
        foreach ($parsers as $name => $class) {
            $this->addParser($name, $class);
        }

        return $this;
    }

    /**
     * Remove a parser
     *
     * @param string $name Parser to remove
     */
    public function removeParser(string $name): void
    {
        unset($this->parsers[$name]);
    }

    /**
     * Set the list of parsers
     *
     * @param array $parsers Associative array of 'name' => 'class'
     */
    public function setParsers(array $parsers = array()): void
    {
        $this->parsers = $parsers;
    }

    // }}}

    // Class factories {{{

    /**
     * Getter for statementClass
     *
     * @return string|callable
     */
    public function getStatementClass()
    {
        return $this->statementClass;
    }

    /**
     * Set the classname of the statement class or callable that returns an object that
     * implements the StatementInterface.
     *
     * The callable is passed the account object and statement sequence number
     * as parameters. Example:
     *
     * $reader->setStatementClass(function (AccountInterface $account, $number) {
     *     return new My\Statement();
     * });
     *
     * If the callable returns null, the statement is skipped.
     *
     * @param string|callable $statementClass
     */
    public function setStatementClass($statementClass): self
    {
        if (!is_callable($statementClass) && !class_exists($statementClass)) {
            throw new \InvalidArgumentException('$statementClass must be a valid classname or a PHP callable');
        }

        $this->statementClass = $statementClass;
        return $this;
    }

    /**
     * Create a Statement object
     *
     * @param AccountInterface $account Account number
     * @param string $number  Statement sequence number
     */
    public function createStatement(AccountInterface $account, string $number): ?StatementInterface
    {
        return $this->createObject($this->statementClass, 'Jejik\MT940\StatementInterface', array($account, $number));
    }

    /**
     * Getter for accountClass
     *
     * @return string|callable
     */
    public function getAccountClass()
    {
        return $this->accountClass;
    }

    /**
     * Set the classname of the account class or callable that returns an object that
     * implements the AccountInterface.
     *
     * The callable is passed the account number as a parameter. Example:
     *
     * $reader->setAccountClass(function ($accountNumber) {
     *     return new My\Account();
     * });
     *
     * If the callable returns null, statements for the account will be skipped.
     *
     * @param string|callable $accountClass
     */
    public function setAccountClass($accountClass): self
    {
        if (!is_callable($accountClass) && !class_exists($accountClass)) {
            throw new \InvalidArgumentException('$accountClass must be a valid classname or a PHP callable');
        }

        $this->accountClass = $accountClass;
        return $this;
    }

    /**
     * Create a Account object
     */
    public function createAccount(string $accountNumber): AccountInterface
    {
        return $this->createObject($this->accountClass, 'Jejik\MT940\AccountInterface', array($accountNumber));
    }

    /**
     * Getter for contraAccountClass
     *
     * @return string|callable
     */
    public function getContraAccountClass()
    {
        return $this->contraAccountClass;
    }

    /**
     * Set the classname of the contraAccount class or callable that returns an object that
     * implements the AccountInterface.
     *
     * The callable is passed the account number as a parameter. Example:
     *
     * $reader->setContraAccountClass(function ($accountNumber) {
     *     return new My\ContraAccount();
     * });
     *
     * @param string|callable $contraAccountClass
     */
    public function setContraAccountClass($contraAccountClass): self
    {
        if (!is_callable($contraAccountClass) && !class_exists($contraAccountClass)) {
            throw new \InvalidArgumentException('$contraAccountClass must be a valid classname or a PHP callable');
        }

        $this->contraAccountClass = $contraAccountClass;
        return $this;
    }

    /**
     * Create a ContraAccount object
     *
     * @param string|null $accountNumber Contra account number
     */
    public function createContraAccount(?string $accountNumber): AccountInterface
    {
        return $this->createObject($this->contraAccountClass, 'Jejik\MT940\AccountInterface', array($accountNumber));
    }

    /**
     * Getter for transactionClass
     *
     * @return string|callable
     */
    public function getTransactionClass()
    {
        return $this->transactionClass;
    }

    /**
     * Set the classname of the transaction class or callable that returns an object that
     * implements the StatementInterface.
     *
     * The callable is not passed any arguments.
     *
     * $reader->setTransactionClass(function () {
     *     return new My\Transaction();
     * });
     *
     * @param string|callable $transactionClass
     */
    public function setTransactionClass($transactionClass): self
    {
        if (!is_callable($transactionClass) && !class_exists($transactionClass)) {
            throw new \InvalidArgumentException('$transactionClass must be a valid classname or a PHP callable');
        }

        $this->transactionClass = $transactionClass;
        return $this;
    }

    /**
     * Create a Transaction object
     */
    public function createTransaction(): TransactionInterface
    {
        return $this->createObject($this->transactionClass, 'Jejik\MT940\TransactionInterface');
    }

    /**
     * Getter for openingBalanceClass
     *
     * @return string|callable
     */
    public function getOpeningBalanceClass()
    {
        return $this->openingBalanceClass;
    }

    /**
     * Set the classname of the opening balance class or callable that returns an object that
     * implements the BalanceInterface.
     *
     * The callable is not passed any arguments.
     *
     * $reader->setOpeningBalanceClass(function () {
     *     return new My\Balance();
     * });
     *
     * @param string|callable $openingBalanceClass
     */
    public function setOpeningBalanceClass($openingBalanceClass): self
    {
        if (!is_callable($openingBalanceClass) && !class_exists($openingBalanceClass)) {
            throw new \InvalidArgumentException('$openingBalanceClass must be a valid classname or a PHP callable');
        }

        $this->openingBalanceClass = $openingBalanceClass;
        return $this;
    }

    /**
     * Create an opening balance object
     */
    public function createOpeningBalance(): BalanceInterface
    {
        return $this->createObject($this->openingBalanceClass, 'Jejik\MT940\BalanceInterface');
    }

    /**
     * Getter for closingBalanceClass
     *
     * @return string|callable
     */
    public function getClosingBalanceClass()
    {
        return $this->closingBalanceClass;
    }

    /**
     * Set the classname of the closing balance class or callable that returns an object that
     * implements the BalanceInterface.
     *
     * The callable is not passed any arguments.
     *
     * $reader->setClosingBalanceClass(function () {
     *     return new My\Balance();
     * });
     *
     * @param string|callable $closingBalanceClass
     */
    public function setClosingBalanceClass($closingBalanceClass): self
    {
        if (!is_callable($closingBalanceClass) && !class_exists($closingBalanceClass)) {
            throw new \InvalidArgumentException('$closingBalanceClass must be a valid classname or a PHP callable');
        }

        $this->closingBalanceClass = $closingBalanceClass;
        return $this;
    }

    /**
     * Create an closing balance object
     */
    public function createClosingBalance(): BalanceInterface
    {
        return $this->createObject($this->closingBalanceClass, 'Jejik\MT940\BalanceInterface');
    }

    /**
     * Create an object of a specified interface
     *
     * @param string|callable $className Classname or a callable that returns an object instance
     * @param string $interface The interface the class must implement
     * @param array $params Parameters to pass to the callable
     *
     * @return object An object that implements the interface
     */
    protected function createObject($className, $interface, $params = array())
    {
        if (is_string($className) && class_exists($className)) {
            $object = new $className();
        } elseif (is_callable($className)) {
            $object = call_user_func_array($className, $params);
        } else {
            throw new \InvalidArgumentException('$className must be a valid classname or a PHP callable');
        }

        if (null !== $object && !($object instanceof $interface)) {
            throw new \InvalidArgumentException(sprintf('%s must implement %s', get_class($object), $interface));
        }

        return $object;
    }

    // }}}

    /**
     * Get MT940 statements from the input text
     *
     * @param string $text
     * @return Statement[]
     * @throws \RuntimeException if no suitable parser is found
     */
    public function getStatements(string $text): array
    {
        if (!$this->parsers) {
            $this->addParsers($this->getDefaultParsers());
        }

        foreach ($this->parsers as $class) {
            $parser = new $class($this);
            if ($parser->accept($text)) {
                return $parser->parse($text);
            }
        }

        throw new \RuntimeException('No suitable parser found.');
    }
}
