<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web\View {

    use Morpho\App\Web\View\Messenger;
    use Morpho\Test\Unit\App\Web\View\MessengerTest\MessageStorage;
    use Morpho\Testing\TestCase;

    class MessengerTest extends TestCase {
        private Messenger $messenger;

        protected function setUp(): void {
            parent::setUp();
            $this->messenger = new Messenger();
            $this->messenger->setMessageStorage(new MessageStorage([]));
        }

        public function testCount() {
            $this->assertInstanceOf('\Countable', $this->messenger);

            $this->assertCount(0, $this->messenger);

            $this->messenger->addErrorMessage("Unknown error has been occurred, please power-off of your machine");

            $this->assertCount(1, $this->messenger);

            $this->messenger->addWarningMessage("A new warning has been occurred again.");

            $this->assertCount(2, $this->messenger);

            $this->messenger->clearMessages();

            $this->assertCount(0, $this->messenger);
        }

        public function testHasMessages() {
            $this->assertFalse($this->messenger->hasErrorMessages());
            $this->messenger->addErrorMessage("Some error.");
            $this->assertTrue($this->messenger->hasErrorMessages());

            $this->assertFalse($this->messenger->hasWarningMessages());
            $this->messenger->addWarningMessage("Some error.");
            $this->assertTrue($this->messenger->hasWarningMessages());
        }

        public function testMessageStorage() {
            $this->messenger->addSuccessMessage('Hello {0} and welcome', ['<b>Name</b>']);
            $this->messenger->addWarningMessage('Bar');
            /** @noinspection PhpParamsInspection */
            $this->assertEquals(
                [
                    Messenger::SUCCESS => [
                        [
                            'text' => 'Hello {0} and welcome',
                            'args' => ['<b>Name</b>'],
                        ],
                    ],
                    Messenger::WARNING => [
                        [
                            'text' => 'Bar',
                            'args' => [],
                        ],
                    ],
                ],
                iterator_to_array($this->messenger->getIterator())
            );
        }
    }
}

namespace Morpho\Test\Unit\App\Web\View\MessengerTest {

    use Morpho\App\Web\View\IMessageStorage;
    use Morpho\Base\ArrIterator;

    class MessageStorage extends ArrIterator implements IMessageStorage {

    }
}
