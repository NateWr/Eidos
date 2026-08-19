<?php
namespace APP\plugins\themes\eidos\classes\components;

use APP\publication\Publication;
use APP\section\Section;
use APP\submission\Submission;
use Closure;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\LazyCollection;
use PKP\context\Context;
use PKP\galley\Galley;
use PKP\facades\ContentHelper;
use PKP\template\ViewHelper;

class ArticleSummary extends Component
{
    public Publication $publication;
    /** @var Galley[] */
    public array $galleys;
    public string $url;
    public string $coverImageUrl = '';
    public string $coverImageAltText = '';

    /**
     * @param Collection<Section> $sections
     */
    public function __construct(
        public string $heading,
        public Submission $submission,
        public ?Context $context,
        public ?bool $showGalleys = true,
        public ?bool $showCover = false,
        public ?bool $showAbstract = false,
        public null|Collection|LazyCollection $sections = null,
    ) {
        $this->publication = $submission->getCurrentPublication();
        $this->url = $this->getUrl();
        $this->galleys = $this->getGalleys();

        if ($this->showCover) {
            $coverImage = $this->publication->getLocalizedData('coverImage');
            if ($coverImage) {
                $this->coverImageUrl = $this->publication->getLocalizedCoverImageUrl($submission->getData('contextId'));
                $this->coverImageAltText = $coverImage['altText'] ?? '';
            }
        }
    }

    public function render(): View|Closure|string
    {
        return view(
            ViewFacade::resolvePluginComponentViewPath(
                $this,
                'components.article-summary'
            )
        );
    }

    protected function getUrl(): string
    {
        $props = [
            'page' => 'article',
            'op' => 'view',
            'path' => $this->submission->getBestId(),
        ];
        if ($this->context) {
            $props['context'] = $this->context->getPath();
        }
        return ViewHelper::url($props);
    }

    /**
     * @return Galley[]
     */
    protected function getGalleys(): array
    {
        if (!$this->showGalleys) {
            return [];
        }

        return ContentHelper::primaryGalleys(
            $this->publication->getData('galleys'),
            (int) $this->submission->getData('contextId')
        );
    }
}