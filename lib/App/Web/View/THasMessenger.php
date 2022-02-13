<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

trait THasMessenger {
    protected Messenger $messenger;

    public function setMessenger(Messenger $messenger): static {
        $this->messenger = $messenger;
        return $this;
    }

    public function messenger(): Messenger {
        return $this->messenger;
    }

    public function clearMessages() {
        $this->messenger->clearMessages();
    }

    public function addSuccessMessage(string $message, array $args = null): static {
        $this->messenger->addSuccessMessage($message, $args);
        return $this;
    }

    public function addInfoMessage(string $text, array $args = null): static {
        $this->messenger->addInfoMessage($text, $args);
        return $this;
    }

    public function addWarningMessage(string $text, array $args = null): static {
        $this->messenger->addWarningMessage($text, $args);
        return $this;
    }

    public function addErrorMessage(string $text, array $args = null): static {
        $this->messenger->addErrorMessage($text, $args);
        return $this;
    }
}
