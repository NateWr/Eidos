<?php
namespace APP\plugins\themes\eidos\classes\components;

use APP\core\Application;
use APP\publication\Publication;
use APP\submission\Submission;
use APP\template\TemplateManager;
use Closure;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use PKP\context\Context;
use PKP\file\FileManager;
use PKP\galley\Galley;
use PKP\template\ViewHelper;

class GalleyLink extends Component
{
    public string $icon;
    public string $url;
    public bool $access;

    public function __construct(
        public Galley $galley,
        public Publication $publication,
        public Submission $submission,
        public ?Context $context,
        public ?string $labelledBy,
        public ?bool $hasAccess = true,
    ) {
        $this->access = $this->getAccess($hasAccess);
        $this->icon = $this->getGalleyIcon($this->access);
        $this->url = $this->getUrl();
    }

    public function render(): View|Closure|string
    {
        return view(
            ViewFacade::resolvePluginComponentViewPath(
                $this,
                'components.galley-link'
            )
        );
    }

    protected function getUrl(): string
    {
        $props = [
            'page' => 'article',
            'op' => 'view',
            'path' => [
                $this->publication->getData('urlPath') ?? $this->submission->getBestId(),
                $this->galley->getBestGalleyId(),
            ],
        ];
        if ($this->context) {
            $props['context'] = $this->context->getPath();
        }
        return ViewHelper::url($props);
    }

    /**
     *  Get the icon to use for this galley
     */
    protected function getGalleyIcon(bool $access): string
    {
        if (!$access) {
            return 'locked';
        }

        if ($this->galley->getData('urlRemote')) {
            return 'remote';
        }

        $type = app()->get('file')->getDocumentType($this->galley->getFileType());

        switch ($type) {
            case FileManager::DOCUMENT_TYPE_PDF:
                return 'pdf';
            case FileManager::DOCUMENT_TYPE_HTML:
            case 'application/xhtml+xml':
                return 'html';
            case 'text/csv':
            case FileManager::DOCUMENT_TYPE_EXCEL:
                return 'spreadsheet';
            default:
                return 'other';
        }
    }

    /**
     * Get whether access is permitted to this galley
     */
    protected function getAccess(bool $hasAccess): bool
    {
        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
        $restrictOnlyPdf = $templateMgr->getTemplateVars('restrictOnlyPdf');

        return $hasAccess || ($restrictOnlyPdf && !$this->galley->isPdfGalley());
    }
}