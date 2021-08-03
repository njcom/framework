<?php declare(strict_types=1);
namespace Morpho\Site\Localhost\App\Web;

use Morpho\Fs\Dir;
use Morpho\App\Web\Controller;

class CacheController extends Controller {
    public function cleanAll() {
        $cacheDirPath = $this->serviceManager['backendModuleIndex']->module($this->serviceManager['site']->moduleName())->cacheDirPath();
        $gitignoreFileExists = file_exists($cacheDirPath . '/.gitignore');
        Dir::delete($cacheDirPath, function (string $path, $isDir) use ($cacheDirPath, $gitignoreFileExists) {
            if ($isDir) {
                return $path !== $cacheDirPath;
            } else {
                if (!$gitignoreFileExists) {
                    return true;
                }
                return $path !== $cacheDirPath . '/.gitignore';
            }
        });
        $this->messenger()->addSuccessMessage('The cache has been cleared successfully');
        return $this->redirect();
    }

    public function cleanRoutes() {
        $this->serviceManager['router']->rebuildRoutes();
        $this->messenger()->addSuccessMessage('Routes have been rebuilt successfully');
        return $this->redirect();
    }
    /**
    public function rebuildEvents() {
        $this->serviceManager['moduleManager']->rebuildEvents();
        $this->redirectToHome("Events were rebuilt successfully");
    }
    */
}
