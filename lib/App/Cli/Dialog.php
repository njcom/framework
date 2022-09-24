<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Cli;

use Morpho\Base\NotImplementedException;
use Morpho\Compiler\Frontend\AsciiStringReader;
use Morpho\Compiler\Frontend\Re;

use RuntimeException;

use function Morpho\Base\caseVal;

// `man dialog`
class Dialog {
    public static function buildList() {
        throw new NotImplementedException();
    }

    public static function calendar() {
        throw new NotImplementedException();
    }

    public static function checkList(iterable $items, string $title = null, string $text = null): array {
        $normalizedItems = [];
        foreach ($items as $key => $val) {
            if (is_numeric($key)) {
                $checked = false;
            } else {
                $checked = (bool)$val;
            }
            $normalizedItems[$key] = escapeshellarg(caseVal($val)) . ($checked ? ' on' : ' off');
        }
        $checkedItemsAsText = (string)sh(
            'dialog --stdout --no-items --title ' . escapeshellarg((string)$title) . ' --checklist ' . escapeshellarg((string)$text) . ' 0 0 0 ' . implode(' ', $normalizedItems),
            ['capture' => true]
        );
        sh('clear');
        $reader = new AsciiStringReader($checkedItemsAsText);
        $checkedItems = [];
        while (true) {
            /** @noinspection PhpStatementHasEmptyBodyInspection */
            if ($reader->readN('~' . Re::WHITESPACES . '~s') > 0) {
            } elseif ($reader->readN('~' . Re::DOUBLE_QUOTED . '~s') > 0) {
                $checkedItems[] = str_replace('\\"', '"', $reader->subgroups()[1]);
            } elseif ($reader->readN('~' . Re::NOT_WHITESPACES . '~s') > 0) {
                $checkedItems[] = $reader->match();
            } else {
                break;
            }
        }
        return $checkedItems;
    }

    public static function dselect() {
        throw new NotImplementedException();
    }

    public static function editBox() {
        throw new NotImplementedException();
    }

    public static function form() {
        throw new NotImplementedException();
        /*
         * @TODO: multiple inputs in the same window
                 shell('dialog --backtitle "Dialog Form Example" --title "Dialog - Form" \
    --form "\nDialog Sample Label and Values" 25 60 16 \
    "Form Label 1:" 1 1 "Value 1" 1 25 25 30 \
    "Form Label 2:" 2 1 "Value 2" 2 25 25 30 \
    "Form Label 3:" 3 1 "Value 3" 3 25 25 30 \
    "Form Label 4:" 4 1 "Value 4" 4 25 25 30; ret=$?; clear; exit $ret');
         */
    }

    public static function fselect(string $path, string $title = null): string|false {
        return self::run($title, '--fselect ' . escapeshellarg($path) . ' 0 80');
    }

    public static function gauge() {
        throw new NotImplementedException();
    }

    public static function infoBox() {
        throw new NotImplementedException();
    }

    public static function inputBox(string $title = null, string $defaultVal = null, string $helpText = null): string|false {
        return self::run($title, '--inputbox ' . escapeshellarg((string)$helpText) . ' 0 0 ' . escapeshellarg((string)$defaultVal));
    }

    public static function inputMenu() {
        throw new NotImplementedException();
    }

    public static function menu(iterable $items, $defaultKey = null, string $title = null, string $text = null): array {
        $normalizedItems = [];
        foreach ($items as $key => $val) {
            $normalizedItems[$key] = caseVal($val);
        }
        $defaultItem = null;
        if (null !== $defaultKey) {
            $defaultItem = $normalizedItems[$defaultKey];
        }
        $el = (string)sh(
            'dialog --stdout --no-items --title ' . escapeshellarg((string)$title) . ' --default-item ' . escapeshellarg(
                (string)$defaultItem
            ) . ' --menu ' . escapeshellarg((string)$text) . ' 0 0 0 ' . arg($normalizedItems),
            ['capture' => true]
        );
        sh('clear');
        $key = array_search($el, $normalizedItems, true);
        return [$key, $el];
    }

    public static function mixedForm() {
        throw new NotImplementedException();
    }

    public static function mixedGauge() {
        throw new NotImplementedException();
    }

    public static function msgBox() {
        throw new NotImplementedException();
    }

    public static function passwordBox() {
        throw new NotImplementedException();
    }

    public static function passwordForm() {
        throw new NotImplementedException();
    }

    public static function pause() {
        throw new NotImplementedException();
    }

    public static function prgBox() {
        throw new NotImplementedException();
    }

    public static function programBox() {
        throw new NotImplementedException();
    }

    public static function progressBox() {
        throw new NotImplementedException();
    }

    public static function radiolist() {
        throw new NotImplementedException();
    }

    public static function rangebox() {
        throw new NotImplementedException();
    }

    public static function tailBox() {
        throw new NotImplementedException();
    }

    public static function tailBoxBg() {
        throw new NotImplementedException();
    }

    public static function textBox() {
        throw new NotImplementedException();
    }

    public static function timeBox() {
        throw new NotImplementedException();
    }

    public static function treeView() {
        throw new NotImplementedException();
    }

    public static function yesNo(string $question): bool {
        $question = escapeshellarg(rtrim($question, '?') . '?');
        $res = sh('dialog --stdout --yesno ' . escapeshellarg($question) . ' 0 0', ['check' => false, 'capture' => true]);
        sh('clear');
        return !$res->isError();
    }

    private static function run(?string $title, string $cmd): string|false {
        $res = sh('dialog --stdout --title ' . escapeshellarg((string)$title) . ' ' . $cmd, ['check' => false, 'capture' => true]);
        sh('clear');
        if ($res->isError()) {
            if ($res->exitCode() !== 1) {
                throw new RuntimeException();
            }
            return false;
        }
        return $res->stdOut();
    }
}