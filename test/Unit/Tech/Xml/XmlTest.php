<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\Xml;

use Morpho\Tech\Xml\Xml;
use Morpho\Testing\TestCase;

use function trim;

class XmlTest extends TestCase {
    public function testArrayToDomDoc() {
        $data = [
            'student_info' => [
                'total_stud' => 500,
                0            => [
                    'student' => [
                        'id'      => 1,
                        'name'    => 'abc',
                        'address' => [
                            'city' => 'Pune',
                            'zip'  => 411006,
                        ],
                    ],
                ],
                1            => [
                    'student' => [
                        'id'      => 2,
                        'name'    => 'xyz',
                        'address' => [
                            'city' => 'Mumbai',
                            'zip'  => 400906,
                        ],
                    ],
                ],
            ],
        ];
        $expected = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<student_info>
  <total_stud>500</total_stud>
  <student>
    <id>1</id>
    <name>abc</name>
    <address>
      <city>Pune</city>
      <zip>411006</zip>
    </address>
  </student>
  <student>
    <id>2</id>
    <name>xyz</name>
    <address>
      <city>Mumbai</city>
      <zip>400906</zip>
    </address>
  </student>
</student_info>
XML;
        $this->assertEquals(trim($expected), trim(Xml::arrayToDomDoc($data)->saveXml()));
    }
}
