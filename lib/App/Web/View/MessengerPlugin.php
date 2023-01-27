<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use Countable;

use function Morpho\Base\{dasherize, format};
use function implode;
use function nl2br;

class MessengerPlugin extends WidgetPlugin implements Countable {
        public function renderPageMessages(): string {
        $html = '';
        $messenger = $this->messenger();
        if ($this->count()) {
            $renderedMessages = [];
            foreach ($messenger as $type => $messages) {
                $renderedMessages[] = $this->renderMessagesOfType($messages, $type);
            }
            $html = $this->formatHtmlContainer($renderedMessages);
        }
        $messenger->clearMessages();
        return $html;
    }

    protected function messenger(): Messenger {
        return $this->serviceManager['messenger'];
    }

    public function count(): int {
        return $this->messenger()->count();
    }

    protected function renderMessagesOfType(iterable $messages, string $type) {
        $renderedMessages = [];
        #$cssClass = $this->messageTypeToCssClass($type);
        foreach ($messages as $message) {
            $renderedMessages[] = $this->renderMessageOfType($message, $type);
        }
        return '<div class="messages ' . $this->messageTypeToCssClass($type) . '">'
            . implode("\n", $renderedMessages)
            . '</div>';
    }

    protected function renderMessageOfType(array $message, string $type): string {
        $text = format(
            nl2br(PhpTemplateEngine::e($message['text'])),
            $message['args'],
            function ($arg) {
                return $arg;
            }
        );
        $cssClass = $this->messageTypeToCssClass($type);
        return '<div class="alert alert-' . $cssClass . ' alert-dismissible fade show" role="alert">' . $text . ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    }

    protected function messageTypeToCssClass(string $type): string {
        $type2CssClass = [
            Messenger::ERROR   => 'danger',
            Messenger::INFO    => 'info',
            Messenger::SUCCESS => 'success',
            Messenger::WARNING => 'warning',
        ];
        return $type2CssClass[$type] ?? dasherize($type);
    }

    protected function formatHtmlContainer(array $renderedMessages): string {
        return '<div id="page-messages">' . implode("\n", $renderedMessages) . '</div>';
    }

    public function __toString(): string {
        return $this->renderPageMessages();
    }
}
