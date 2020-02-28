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

namespace Jejik\MT940\Parser;

/**
 * Parser for KNAB documents
 *
 * @author Casper Bakker <github@casperbakker.com>
 */
class Knab extends AbstractParser
{

    /**
     * Test if the document is an KNAB document
     *
     * @param string $text
     * @return bool
     */
    public function accept($text): bool
    {
        return strpos(strtok($text, "\n"), 'KNABNL') !== false;
    }

    /**
     * Get the closing balance
     *
     * @param mixed $text
     *
     * @return \Jejik\MT940\Balance|null
     */
    protected function closingBalance($text): ?\Jejik\MT940\Balance
    {
        if ($line = $this->getLine('62M|62F', $text)) {
            return $this->balance($this->reader->createClosingBalance(), $line);
        }

        return null;
    }

    /**
     * Get the contra account number from a transaction
     *
     * @param array $lines The transaction text at offset 0 and the description at offset 1
     * @return string|null
     */
    protected function contraAccountNumber(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/REK\: ([a-zA-Z]{2}[0-9]{2}[a-zA-Z0-9]{4}[0-9]{7}([a-zA-Z0-9]?){0,16})/', $line, $match)) {
                return rtrim(ltrim($match[1], '0P'));
            }
        }

        return null;
    }

    protected function contraAccountName(array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match('/NAAM: (.+)/', $line, $match)) {
                return trim($match[1]);
            }
        }

        return null;
    }
}
