# Proposal for built-in Layout component

We could use a core `Layout` component to register custom data, handle `<head>` elements, and add helper functions. In the long run, this could help isolate theme code from editorial backend code by extracting theme code out of `PKPTemplateManager`.

The built-in layout component template.

```html
<!-- lib/pkp/templates/layouts/layout.blade -->

<!doctype html>
<html lang="{{ str_replace('_', '-', $currentLocale) }}" xml:lang="{{ str_replace('_', '-', $currentLocale) }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }}</title>
    @if ($description)
        <meta name="description" content="{{ strip_tags($description) }}">
    @endif
    @loadHeader(['context' => 'frontend'])
    @loadStylesheet(['context' => 'frontend'])
    {{ $head }}
</head>

<body>

    {{ $slot }}

    @loadScript(['context' => 'frontend'])

    {{ $foot }}
</body>

</html>
```

A theme would extend this component with it's own layout template (notice the use of component namespacing):

```html
<!-- plugins/themes/eidos/templates/layouts/layout.blade -->

<x-app::layout
    title="{{ $title }}"
    description="{{ $description }}"
>
    <header>...</header>
    <main>
        {{ $slot }}
    </main>
    <footer>...</footer>
</x-app::layout>
```

A theme would then use its own layout component in its pages.

```html
<!-- plugins/themes/eidos/templates/frontend/indexJournal.blade -->

<x-layout
    title="Journal of Public Knowledge"
    description="..."
>
    ...page content..
</x-layout>
```

For this to work, the template hierarchy would need to fall through, so that a call to `<x-layout>` look at:

* `plugins/themes/<current-theme>/templates/layouts/layout.blade`
* `templates/layouts/layout.blade`
* `lib/pkp/templates/layouts/layout.blade`


## The `$head` and `$foot` slots.

The built-in component has a slot in the `<head>` and another just before the `</body>`. Theme developers can use this to load scripts, styles and other assets, using approaches common in modern frontend toolsets (Astro, React Helmet, Nuxt, etc).

```html
<!-- plugins/themes/eidos/templates/layouts/layout.blade -->

<x-app::layout
    title="{{ $title }}"
    description="{{ $description }}"
>
    <x-slot:head>
        <link rel="stylesheet" src="{{ $pluginUrl }}/my-stylesheet.css">
        <meta name="custom-meta" content="...">
    </x-slot:head>

    <header>...</header>
    <main>
        {{ $slot }}
    </main>
    <footer>...</footer>

    <x-slot:foot>
        <script type="module" src="..."></script>
    </x-slot:foot>
</x-app::layout>
```

The `@loadHeader`, `@loadStylesheet` and `@loadScript` directives would still be used by plugins, but theme developers don't need to know about them.

## Component class

> I tried to set this up, but I couldn't figure out the right places to put the `Layout` classes in pkp-lib, ojs, etc, so that it all extended and referenced a hierarchy of templates. Probably Touhidur can figure this out...

The built-in component has a base class that provides data and helper functions.

```php
<?php
namespace PKP\view\components;

use APP\core\Application;
use Closure;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;

class Layout extends Component
{
    public function __construct(
        public string $title,
        public string $description = '',
    ) {
        //
    }

    public function render(): View|Closure|string
    {
        return view(
            ViewFacade::resolvePluginComponentViewPath(
                $this,
                'components.layout'
            )
        );
    }

    /**
     * Get the <title> by combining the current page title
     * with the context or site name.
     */
    public function pageTitle() : string
    {
        $page = Application::get()->getRequest()->getRequestedPage();
        $context = Application::get()->getRequest()->getContext();

        if ($page === 'index') {
            return $this->title;
        }

        $name = $context
            ? $context->getLocalizedName()
            : Application::get()->getRequest()->getSite()->getLocalizedTitle();

        return $this->title . __('common.titleSeparator') . $name;
    }
}
```

Eventually, much of the global template data for the frontend can be moved out of `PKPTemplateManager` and into this class.

```php
class Layout extends Component
{
    public function __construct(...) {
        /**
         * Global data needs to be added differently from component data
         */
        view()->share([
            'currentContext' => Application::get()->getRequest()->getContext(),
            'currentLocale' => Locale::getLocale(),
            ...

            // Helper functions can be made global this way too
            'pageTitle' => [$this, 'pageTitle'],
        ]);
    }
}
```

Extend the `pkp-lib` component to add app-specific data.

```php
namespace APP\view\components;

class Layout extends PKP\view\components\Layout
{
    ...
}
```

And finally, themes can extend this layout component themselves to add their own data or override built-in helper functions.

```php
namespace APP\plugins\themes\eidos\classes\components;

use APP\view\components\Layout as OJSLayout;

class Layout extends OJSLayout
{
    public function __construct(...)
    {
        view()->share('myCustomData', '...');
    }

    public function pageTitle(): string
    {
        $title = parent::pageTitle();

        return $title . ' (My Institution)';
    }
}
```

## Body attributes

Attributes passed to the component are assigned to to the `<body>` tag.


```html
<!-- plugins/themes/eidos/templates/layouts/layout.blade -->

<x-app::layout
    title="{{ $title }}"
    description="{{ $description }}"
    class="my-custom-class"
    data-vue-root=""
>
    <header>...</header>
    <main>
        {{ $slot }}
    </main>
    <footer>...</footer>
</x-app::layout>
```

The body class would merge the attributes:

```html
<body
    class="pkp-page-index pkp-op-index my-custom-class"
    data-vue-root
>
```

## Escape!

Theme developers don't have to use the built-in layout. They can avoid all of it by just not calling `<x-layout>` in their templates.

The only use case I can think of here is if I wanted to go total Vue/React frontend. In that case, it's probably more performant to avoid all of the setup that goes into rendering a template.
