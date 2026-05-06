# Proposals for using custom Vue components in themes

It's possible to use [Reka UI](https://reka-ui.com/) components on the frontend by extending the `load_frontend.js` file in `lib/pkp`.

```js
// lib/pkp/js/load_frontend.js

import {
	PopoverAnchor,
	PopoverArrow,
	PopoverClose,
	PopoverContent,
	PopoverPortal,
	PopoverRoot,
	PopoverTrigger,
} from 'reka-ui'

VueRegistry.registerComponent('PopoverAnchor', PopoverAnchor);
VueRegistry.registerComponent('PopoverArrow', PopoverArrow);
VueRegistry.registerComponent('PopoverClose', PopoverClose);
VueRegistry.registerComponent('PopoverContent', PopoverContent);
VueRegistry.registerComponent('PopoverPortal', PopoverPortal);
VueRegistry.registerComponent('PopoverRoot', PopoverRoot);
VueRegistry.registerComponent('PopoverTrigger', PopoverTrigger);
```

Once re-compiled, the elements can be used in a theme like this:

```html
<div data-vue-root>
    <popover-root class="dropdown">
        <popover-trigger>
            ...button...
        </popover-trigger>
        <popover-portal>
            <popover-content
                class="dropdown-content"
                align="end"
                side="bottom"
                :side-offset="5"
            >
                ...content...
                <popover-close
                    class="dropdown-close sr-only focus-visible:not-sr-only"
                    aria-label="{{ __('common.close') }}"
                >
                    @include('components/icons/close')
                </popover-close>
                <popover-arrow class="dropdown-arrow"></popover-arrow>
            </popover-content>
        </popover-portal>
    </popover-root>
</div>
```

We have almost all of the pieces ready for a theme to import and register components itself.

```js
// plugins/themes/eidos/src/js/dropdown.js

import {
	PopoverAnchor,
	PopoverArrow,
	PopoverClose,
	PopoverContent,
	PopoverPortal,
	PopoverRoot,
	PopoverTrigger,
} from 'reka-ui'

document.addEventListener('DOMContentLoaded',function() {
  if (!pkp?.registry) {
    console.log('No Vue registry found. Unable to load dropdown component.')
    return
  }
  pkp.registry.registerComponent('PopoverAnchor', PopoverAnchor);
  pkp.registry.registerComponent('PopoverArrow', PopoverArrow);
  pkp.registry.registerComponent('PopoverClose', PopoverClose);
  pkp.registry.registerComponent('PopoverContent', PopoverContent);
  pkp.registry.registerComponent('PopoverPortal', PopoverPortal);
  pkp.registry.registerComponent('PopoverRoot', PopoverRoot);
  pkp.registry.registerComponent('PopoverTrigger', PopoverTrigger);
})
```

However, here we run into the challenge with the IIFE build format. The function which looks for `data-vue-root` and initializes Vue components runs before a theme's JavaScript modules. I think there are two ways we can handle this.

## 1. Allow themes to mount a Vue app on their own

> I tried this approach, but ran into an error when the mounting happened that was deep in the call stack.

Currently, if a theme loads the Vue runtime, OJS runs the following code to mount Vue apps.

```js
document.addEventListener('DOMContentLoaded', () => {

    ...

	pkp.registry.initVueFromAttributes();
});
```

We could pass a selector to `initVueFromAttribute` instead, and allow themes to call the function again after they have registered their components. First, we'd change the function in `pkp-lib`.

```diff
// lib/pkp/js/classes/VueRegistry.js

- initVueFromAttributes() {
-    document.querySelectorAll('[data-vue-root]')
+ initVue(selector) {
+    document.querySelectorAll(selector)
```

Then a theme could initialize a Vue app on any selector it wants, after it has registered all new components.

```js
// plugins/themes/eidos/src/js/dropdown.js

import {
	PopoverAnchor,
	PopoverArrow,
	PopoverClose,
	PopoverContent,
	PopoverPortal,
	PopoverRoot,
	PopoverTrigger,
} from 'reka-ui'

document.addEventListener('DOMContentLoaded',function() {
  if (!pkp?.registry) {
    console.log('No Vue registry found. Unable to load dropdown component.')
    return
  }
  pkp.registry.registerComponent('PopoverAnchor', PopoverAnchor);
  pkp.registry.registerComponent('PopoverArrow', PopoverArrow);
  pkp.registry.registerComponent('PopoverClose', PopoverClose);
  pkp.registry.registerComponent('PopoverContent', PopoverContent);
  pkp.registry.registerComponent('PopoverPortal', PopoverPortal);
  pkp.registry.registerComponent('PopoverRoot', PopoverRoot);
  pkp.registry.registerComponent('PopoverTrigger', PopoverTrigger);

  pkp.registry.initVue('[data-eidos-vue-root]')
})
```

## 2. Allow apps to import PKP components and choose when to mount a Vue app

Right now, a plugin calls `$this->requiresVueRuntime()` and PKP loads the pre-compiled runtime. This is good for themes that will have no build process of their own. As a secondary option, we could allow themes to import PKP's run-time configuration in their own build process.

```js
// plugins/themes/eidos/src/js/dropdown.js

/**
 * Import core dependencies for initializing a
 * Vue app.
 */
import {
    pkpVue
} from '../../lib/pkp/js/vue-core.js'

/**
 * Import the PKP components that I need
 */
import PkpComments from '@/frontend/components/PkpComments/PkpComments.vue';
import PkpCommentReportDialog from '@/frontend/components/PkpComments/PkpCommentReportDialog.vue';

/**
 * Import any third-party components I want t o use
 */
import {
	PopoverAnchor,
	PopoverArrow,
	PopoverClose,
	PopoverContent,
	PopoverPortal,
	PopoverRoot,
	PopoverTrigger,
} from 'reka-ui'


pkpVue.registry.registerComponent('PkpComments', PkpComments);
pkpVue.registry.registerComponent('PkpCommentReportDialog', PkpCommentReportDialog);
pkpVue.registry.registerComponent('PopoverAnchor', PopoverAnchor);
pkpVue.registry.registerComponent('PopoverArrow', PopoverArrow);
pkpVue.registry.registerComponent('PopoverClose', PopoverClose);
pkpVue.registry.registerComponent('PopoverContent', PopoverContent);
pkpVue.registry.registerComponent('PopoverPortal', PopoverPortal);
pkpVue.registry.registerComponent('PopoverRoot', PopoverRoot);
pkpVue.registry.registerComponent('PopoverTrigger', PopoverTrigger);

document.addEventListener('DOMContentLoaded',function() {
    pkpVue.registry.initVue('[data-vue-root]')
})
```

I'm not sure what technical hurdles exist for this approach, but I like that it looks and works exactly like a normal ES6 module import structure. A theme developer could use PKP's off-the-shelf components for peer review and commenting, while also importing their own preferred library or framework for other components.

The other upside is that PKP doesn't have to decide which Reka UI components to include or exclude from its pre-built package.
