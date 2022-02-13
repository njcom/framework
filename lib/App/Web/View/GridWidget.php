<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Web\View;

use Closure;
use Morpho\Base\IConfigurable;
use Morpho\Base\IHasServiceManager;
use Morpho\Base\IServiceManager;
use Stringable;

use function Morpho\Base\prepend;

class GridWidget extends Plugin implements Stringable, IHasServiceManager, IConfigurable {
    protected iterable $dataSource;
    protected array $actions = [];

    protected PhpTemplateEngine $templateEngine;
    /**
     * @var string[]
     */
    protected array $columns = [];

    protected ?Closure $rowRenderer = null;
    private ?string $cellType = null;
    private array $btns = [];
    private mixed $conf;

    public function __construct() {
        //parent::__construct();
        $this->columns = [
            'id'    => 'ID',
            'title' => 'Name',
            'ltype' => 'Type',
            'descr' => 'Description',
        ];
        $this->actions = [
            'edit'   => 'Edit',
            'delete' => 'Delete',
        ];
    }

    public function setConf(mixed $conf): static {
        $this->conf = $conf;
        return $this;
    }

    public function conf(): mixed {
        return $this->conf;
    }

    public function setServiceManager(IServiceManager $serviceManager): static {
        $this->templateEngine = $serviceManager['templateEngine'];
        return $this;
    }

    public function setDataSource(iterable $dataSource): static {
        $this->dataSource = $dataSource;
        return $this;
    }

    public function appendColumns(array $columns): static {
        $this->columns = array_merge($this->columns, $columns);
        return $this;
    }

    public function setColumns(array $columns): static {
        $this->columns = $columns;
        return $this;
    }

    public function columns(): array {
        return $this->columns;
    }

    public function setBtns(array $btns): static {
        $this->btns = $btns;
        return $this;
    }

    public function prependActions(array $actions): static {
        $this->actions = array_merge($actions, $this->actions);
        return $this;
    }

    public function __toString(): string {
        $gridHtmlIdAttr = $this->templateEngine->htmlId($this->cellType ? $this->cellType . 'Grid' : 'grid');
        ob_start();
        ?>
        <div class="table-responsive grid" id="<?= $gridHtmlIdAttr ?>">
            <?php echo $this->renderActionBtns() ?>
            <table class="table table-striped table-hover table-dark m-0">
                <thead>
                <tr>
                    <th style="width: 1%"><input type="checkbox" value="1" class="grid__chk grid__chk-all"></th>
                    <?php foreach ($this->columns() as $name => $title) {
                        echo '<th id="' . $this->templateEngine->htmlId($name) . '">' . $this->templateEngine->e($title) . '</th>';
                    } ?>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($this->dataSource as $cell) {
                    echo $this->renderRow($cell);
                }
                ?>
                </tbody>
            </table>
            <template id="<?= $gridHtmlIdAttr ?>-row-tpl">
                <?= $this->renderRow(array_combine(array_keys($this->columns), prepend(array_keys($this->columns), '$'))); ?>
            </template>
        </div>
        <?php
        return ob_get_clean();
    }

    public function setRowRenderer(Closure $fn): static {
        $this->rowRenderer = $fn;
        return $this;
    }

    public function setCellType(string $cellType): static {
        $this->cellType = $cellType;
        return $this;
    }

    private function renderRow(array $cell): string {
        ob_start();
        if ($this->rowRenderer) {
            echo ($this->rowRenderer)->bindTo($this, $this)($cell);
        } else {
            ?>
            <tr<?= $this->templateEngine->attribs(['id' => $this->cellType($cell) . '-' . $cell['id']]) ?>>
                <td><?= $this->templateEngine->checkboxControl(
                        ['name' => $this->cellType($cell) . '[' . $cell['id'] . ']', 'class' => 'grid__chk grid__chk-one']
                    ) ?></td>
                <?php foreach ($this->columns() as $name => $_) {
                    echo $this->templateEngine->tag(
                        'td',
                        nl2br($this->templateEngine->e($cell[$name])),
                        ['class' => $this->cellType($cell) . '-' . $name],
                        ['escape' => false]
                    );
                } ?>
                <td class="actions">
                    <div class="btn-group btn-group-sm">
                        <?= $this->renderRowBtns() ?>
                    </div>
                </td>
            </tr>
            <?php
        }
        return ob_get_clean();
    }

    private function renderActionBtns(): string {
        $html = '';
        $btns = $this->btns ?: $this->defaultBtns();
        foreach ($btns as $name => $title) {
            $html .= $this->templateEngine->buttonControl($title, ['class' => 'grid__action-btn disabled action-btn btn btn-secondary ' . $name]);
        }
        return '<div class="grid__action-btns btn-toolbar my-3">' . $html . '</div>';
    }

    private function renderRowBtns(): string {
        $html = '';
        foreach ($this->actions as $actionId => $title) {
            $html .= $this->templateEngine->buttonControl(
                $title,
                ['type' => 'button', 'class' => 'action-btn btn btn-secondary ' . $actionId]
            );
        }
        return $html;
    }

    private function cellType(array $cell): string {
        if (isset($cell['ltype'])) {
            return $cell['ltype'];
        }
        return $this->cellType;
    }

    private function defaultBtns(): array {
        return [
            'delete-selected' => 'Delete selected',
        ];
    }
}