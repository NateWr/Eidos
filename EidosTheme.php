<?php
namespace APP\plugins\themes\eidos;

use APP\core\Application;
use APP\plugins\themes\eidos\classes\ViteLoader;
use APP\template\TemplateManager;
use PKP\plugins\ThemePlugin;

class EidosTheme extends ThemePlugin {

    public function init() {
        $this->requiresVueRuntime();
        $this->addViteAssets(['src/main.js']);
        $this->addMenuArea(['primary', 'user', 'homepage', 'policy']);
    }

    public function getDisplayName() {
        return __('plugins.themes.eidos.name');
    }

    public function getDescription() {
        return __('plugins.themes.eidos.description');
    }

    /**
     * Add the script, style and other assets compiled by Vite
     *
     * @param array $args Pass arguments to ThemePlugin::addStyle() or TemplateManager::addStylesheet()
     */
    protected function addViteAssets(array $entryPoints, ?array $args = null): void
    {
        $templateMgr = TemplateManager::getManager(
            Application::get()->getRequest()
        );

        $viteLoader = new ViteLoader(
            templateManager: $templateMgr,
            manifestPath: dirname(__FILE__) . '/dist/.vite/manifest.json',
            serverPath: join('/', [dirname(__FILE__), '.vite.server.json']),
            buildUrl: join('/', [$this->getPluginUrl(), 'dist/']),
            prefix: $this->getPluginPath(),
            args: $args
        );

        $viteLoader->load($entryPoints);
    }

    /**
     * Get the URL to the theme's root directory
     */
    public function getPluginUrl(): string
    {
        $request = Application::get()->getRequest();
        $baseUrl = rtrim($request->getBaseUrl(), '/');
        $pluginPath = rtrim($this->getPluginPath(), '/');
        return "{$baseUrl}/{$pluginPath}";
    }
}
