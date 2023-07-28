<?php declare(strict_types=1);

use Morpho\Compiler\Frontend\MbStringReader;

use function Morpho\App\Cli\showSep;

require 'cli.php';

$s = 'Fri Dec 12 1975 14:39';
$re = '~Fri~';

$reader1 = new MbStringReader($s);
$res1 = $reader1->read($re);

$reader2 = new MbStringReader($s);
$res2 = $reader2->readUntil($re);

var_dump(
    $res1,
    $reader1->offset(),
    $reader1->match(),
    $reader1->groups(),
);
showSep();
var_dump(
    $res2,
    $reader2->offset(),
    $reader2->match(),
    $reader2->groups(),
);