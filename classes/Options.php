<?php

namespace APP\plugins\themes\eidos\classes;

use APP\core\Application;
use APP\journal\Journal;
use APP\plugins\themes\eidos\EidosTheme;
use APP\template\TemplateManager;
use Illuminate\Support\Collection;
use PKP\view\MetadataBlock;

/**
 * Helper class to add theme options
 */
class Options
{
    public const HEADER_DEFAULT = 'default';
    public const HEADER_BOXED = 'boxed';
    public const HEADER_LINE = 'line';

    public const HOMEPAGE_IMAGE_POSITION_ABOVE = 'above';
    public const HOMEPAGE_IMAGE_POSITION_BEHIND = 'behind';
    public const HOMEPAGE_IMAGE_POSITION_BELOW = 'below';
    public const HOMEPAGE_IMAGE_POSITION_NONE = 'none';

    public const FONT_DEFAULT = 'noto-sans';

    public const SITE_WIDTH_FULL = 'full';
    public const SITE_WIDTH_FIXED = 'fixed';

    public const ISSUE_ARCHIVE_DEFAULT = 'default';
    public const ISSUE_ARCHIVE_COVERS = 'covers';
    public const ISSUE_ARCHIVE_LIST = 'list';

    public const ARTICLE_HIGHLIGHT_METADATA_DEFAULT = [
        'metadata.doi',
    ];

    public const ARTICLE_SIDEBAR_METADATA_DEFAULT = [
        'metadata.version',
        'metadata.date-published',
        'metadata.date-submitted',
        'metadata.metrics',
    ];

    /**
     * Primary locale of current context
     */
    protected string $primaryLocale;

    /**
     * Current context
     */
    protected Journal $context;

    public function __construct(
        /**
         * Instance of the theme
         */
        protected EidosTheme $theme,

        /**
         * List of enabled fonts
         *
         * From the Google Fonts plugin. Empty array
         * if the plugin is disabled or no fonts have
         * been added through the plugin.
         */
        protected array $enabledFonts
    ) {
        $request = Application::get()->getRequest();
        $this->context = $request->getContext();
        $this->primaryLocale = $this->context
            ? $this->context->getPrimaryLocale()
            : $request->getSite()->getPrimaryLocale();
    }

    /**
     * Add all theme options
     */
    public function addOptions(): void
    {
        $this->addHeaderOption();
        $this->addHomepageImageOption();
        $this->addTaglineOption();
        $this->addSiteWidthOption();
        $this->addFontOptions();
        $this->addIssueArchivesOption();
        $this->addArticleHighlightMetadataOption();
        $this->addArticleSidebarMetadataOption();
    }

    /**
     * Add option for the header layout
     */
    protected function addHeaderOption(): void
    {
        $this->theme->addOption('header', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.eidos.option.header.label'),
            'description' => __('plugins.themes.eidos.option.header.description'),
            'options' => [
                [
                    'value' => self::HEADER_DEFAULT,
                    'label' => __('plugins.themes.eidos.option.header.default'),
                ],
                [
                    'value' => self::HEADER_BOXED,
                    'label' => __('plugins.themes.eidos.option.header.default-boxed'),
                ],
                [
                    'value' => self::HEADER_LINE,
                    'label' => __('plugins.themes.eidos.option.header.line'),
                ],
            ],
            'default' => self::HEADER_DEFAULT,
        ]);
    }

    /**
     * Get CSS variables based on the theme options
     */
    public function getCssVariables(): Collection
    {
        $variables = new Collection([]);

        if ($this->usesCustomFonts()) {
            foreach ($this->enabledFonts as $font) {
                if ($font['id'] === $this->theme->getOption('font')) {
                    $variables['--font-base'] = "'{$font['family']}', {$this->getFontFallback($font['category'])}";
                }
                if ($font['id'] === $this->theme->getOption('titlesFont')) {
                    $variables['--font-titles'] = "'{$font['family']}', {$this->getFontFallback($font['category'])}";
                }
                if ($font['id'] === $this->theme->getOption('actionsFont')) {
                    $variables['--font-actions'] = "'{$font['family']}', {$this->getFontFallback($font['category'])}";
                }
            }
        }

        return $variables;
    }

    /**
     * Get a CSS string that assigns all variables to
     * the passed CSS selector
     *
     * For example, if $selector='body' it will return:
     *
     * body {
     *    // variables
     * }
     */
    public function getCssVariablesString(string $selector = 'body'): string
    {
        $string = $this->getCssVariables()
            ->map(fn($val, $var) => "{$var}: {$val};")
            ->join('');

        return "{$selector} {{$string}}";
    }

    /**
     * Add option for where to display the homepage image
     */
    protected function addHomepageImageOption(): void
    {
        $this->theme->addOption('homepageImagePosition', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.eidos.option.homepageImagePosition.label'),
            'description' => __('plugins.themes.eidos.option.homepageImagePosition.description'),
            'options' => [
                [
                    'value' => self::HOMEPAGE_IMAGE_POSITION_ABOVE,
                    'label' => __('plugins.themes.eidos.option.homepageImagePosition.above'),
                ],
                [
                    'value' => self::HOMEPAGE_IMAGE_POSITION_BEHIND,
                    'label' => __('plugins.themes.eidos.option.homepageImagePosition.behind'),
                ],
                [
                    'value' => self::HOMEPAGE_IMAGE_POSITION_BELOW,
                    'label' => __('plugins.themes.eidos.option.homepageImagePosition.below'),
                ],
                [
                    'value' => self::HOMEPAGE_IMAGE_POSITION_NONE,
                    'label' => __('plugins.themes.eidos.option.homepageImagePosition.none'),
                ],
            ],
            'default' => self::HOMEPAGE_IMAGE_POSITION_ABOVE,
        ]);
    }

    /**
     * Add option for the tagline to display beside the logo
     */
    protected function addTaglineOption(): void
    {
        $this->theme->addOption('tagline', 'FieldText', [
            'label' => __('plugins.themes.eidos.option.tagline.label'),
            'description' => __('plugins.themes.eidos.option.tagline.description'),
            'isMultilingual' => true,
        ]);
    }

    /**
     * Add option to set the site width
     */
    protected function addSiteWidthOption(): void
    {
        $this->theme->addOption('siteWidth', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.eidos.option.siteWidth.label'),
            'description' => __('plugins.themes.eidos.option.siteWidth.description'),
            'options' => [
                [
                    'value' => self::SITE_WIDTH_FULL,
                    'label' => __('plugins.themes.eidos.option.siteWidth.full'),
                ],
                [
                    'value' => self::SITE_WIDTH_FIXED,
                    'label' => __('plugins.themes.eidos.option.siteWidth.fixed'),
                ],
            ],
            'default' => self::SITE_WIDTH_FULL,
        ]);
    }

    /**
     * Add options to set typography
     */
    protected function addFontOptions(): void
    {
        if (!count($this->enabledFonts)) {
            return;
        }

        $options = [];
        foreach ($this->enabledFonts as $font) {
            $options[] = [
                'value' => $font['id'],
                'label' => $font['family'],
            ];
        }

        $this->theme->addOption('font', 'FieldSelect', [
            'label' => __('plugins.themes.eidos.option.font.label'),
            'description' => __('plugins.themes.eidos.option.font.description'),
            'options' => $options,
            'default' => self::FONT_DEFAULT,
        ]);

        $this->theme->addOption('titlesFont', 'FieldSelect', [
            'label' => __('plugins.themes.eidos.option.titlesFont.label'),
            'description' => __('plugins.themes.eidos.option.titlesFont.description'),
            'options' => $options,
            'default' => self::FONT_DEFAULT,
        ]);

        $this->theme->addOption('actionsFont', 'FieldSelect', [
            'label' => __('plugins.themes.eidos.option.actionsFont.label'),
            'description' => __('plugins.themes.eidos.option.actionsFont.description'),
            'options' => $options,
            'default' => self::FONT_DEFAULT,
        ]);
    }

    /**
     * Get the fallback font statement based on a font category
     *
     * The category is usually serif or sans-serif, but may be
     * other categories from Google Fonts, such as display and
     * handwriting.
     */
    protected function getFontFallback(string $category): string
    {
        switch ($category) {
            case 'serif':
                return 'serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"';
            case 'sans-serif':
            default:
                return 'system-ui, -apple-system, BlinkMacSystemFont, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"';
        }
    }

    /**
     * Whether or not this theme uses custom fonts
     *
     * Checks if Google Fonts have been enabled and the theme
     * option has been set.
     */
    public function usesCustomFonts(): bool
    {
        return count($this->enabledFonts)
            && (
                $this->theme->getOption('font')
                || $this->theme->getOption('titlesFont')
                || $this->theme->getOption('actionsFont')
            );
    }

    /**
     * Add option to change how issues are displayed in
     * the issue archive
     */
    protected function addIssueArchivesOption(): void
    {
        $this->theme->addOption('issueArchives', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.eidos.option.issueArchives.label'),
            'description' => __('plugins.themes.eidos.option.issueArchives.description'),
            'options' => [
                [
                    'value' => self::ISSUE_ARCHIVE_DEFAULT,
                    'label' => __('plugins.themes.eidos.option.issueArchives.default'),
                ],
                [
                    'value' => self::ISSUE_ARCHIVE_COVERS,
                    'label' => __('plugins.themes.eidos.option.issueArchives.covers'),
                ],
                [
                    'value' => self::ISSUE_ARCHIVE_LIST,
                    'label' => __('plugins.themes.eidos.option.issueArchives.list'),
                ],
            ],
            'default' => self::ISSUE_ARCHIVE_DEFAULT,
        ]);
    }

    /**
     * Add option to highlight some metadata at the top
     * of the article landing page
     */
    protected function addArticleHighlightMetadataOption(): void
    {
        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
        $blocks = $templateMgr->metadataBlocks->get();

        $this->theme->addOption('highlightArticleMetadata', 'FieldOptions', [
            'type' => 'checkbox',
            'isOrderable' => true,
            'label' => __('plugins.themes.eidos.option.highlightArticleMetadata.label'),
            'description' => __('plugins.themes.eidos.option.highlightArticleMetadata.description'),
            'options' => $blocks->map(
                fn(MetadataBlock $block) => [
                    'value' => $block->id,
                    'label' => $block->title,
                ])
                ->values(),
            'default' => self::ARTICLE_HIGHLIGHT_METADATA_DEFAULT,
        ]);
    }

    /**
     * Add option to show some metadata in the sidebar
     * of the article landing page
     */
    protected function addArticleSidebarMetadataOption(): void
    {
        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
        $blocks = $templateMgr->metadataBlocks->get();

        $this->theme->addOption('sidebarArticleMetadata', 'FieldOptions', [
            'type' => 'checkbox',
            'isOrderable' => true,
            'label' => __('plugins.themes.eidos.option.sidebarArticleMetadata.label'),
            'description' => __('plugins.themes.eidos.option.sidebarArticleMetadata.description'),
            'options' => $blocks->map(
                fn(MetadataBlock $block) => [
                    'value' => $block->id,
                    'label' => $block->title,
                ])
                ->values(),
            'default' => self::ARTICLE_SIDEBAR_METADATA_DEFAULT,
        ]);
    }
}
