<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Web\View;

use Morpho\App\Web\View\IMessageStorage;
use Morpho\App\Web\View\Messenger;
use Morpho\App\Web\View\MessengerPlugin;
use Morpho\Base\ArrIterator;
use Morpho\Base\ServiceManager;
use Morpho\Testing\TestCase;

class MessengerPluginTest extends TestCase {
    /**
     * @var Messenger
     */
    private $messenger;
    /**
     * @var MessengerPlugin
     */
    private $messengerPlugin;

    protected function setUp(): void {
        parent::setUp();
        $this->messenger = new Messenger();
        $this->messenger->setMessageStorage(new MessageStorage());
        $serviceManager = new ServiceManager();
        $serviceManager['messenger'] = $this->messenger;

        $this->messengerPlugin = new MessengerPlugin();
        $this->messengerPlugin->setServiceManager($serviceManager);
    }

    public function testRenderPageMessages_EscapesTextWithoutArgs() {
        $this->messenger->addWarningMessage("<strong>Important</strong> warning.");
        $expected = <<<OUT
<div id="page-messages">
    <div class="messages warning">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            &lt;strong&gt;Important&lt;/strong&gt; warning. <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    </div>
</div>
OUT;
        $this->assertHtmlEquals($expected, $this->messengerPlugin->renderPageMessages());
    }

    public function testRenderPageMessages_EscapesTextButDoesNotEscapeArgs() {
        $this->messenger->addWarningMessage(
            '<div>Random {0} warning "!" {1} has been occurred.</div>',
            ['<b>system</b>', '<div>for <b>unknown</b> reason</div>']
        );
        $expected = <<<OUT
<div id="page-messages">
    <div class="messages warning">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            &lt;div&gt;Random <b>system</b> warning &quot;!&quot; <div>for <b>unknown</b> reason</div> has been occurred.&lt;/div&gt; <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    </div>
</div>
OUT;
        $this->assertHtmlEquals($expected, $this->messengerPlugin->renderPageMessages());
    }
}

class MessageStorage extends ArrIterator implements IMessageStorage {
}
