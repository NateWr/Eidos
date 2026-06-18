<?php

namespace APP\plugins\themes\eidos\classes;

use APP\plugins\themes\eidos\EidosTheme;
use APP\view\HomepageBlocksRegistry;
use PKP\view\HomepageBlock;

/**
 * Helper class to add metadata blocks
 */
class HomepageBlocks
{
    public function __construct(
        /**
         * Instance of the theme
         */
        protected EidosTheme $theme,
    ) {
        //
    }

    /**
     * Register custom homepage blocks
     */
    public function register(HomepageBlocksRegistry $blocks): void
    {
        $blocks->register(
            new HomepageBlock(
                component: 'homepage.how-to-submit',
                title: __('plugins.themes.eidos.option.homepageBlocks.how-to-submit'),
            )
        );
        $blocks->register(
            new HomepageBlock(
                component: 'homepage.about',
                title: __('plugins.themes.eidos.option.homepageBlocks.about'),
            )
        );
    }
}