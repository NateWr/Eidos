import { PopoverAnchor, PopoverArrow, PopoverClose, PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui'

const init = () => {
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

  // Example of calling the init vue code at a time of my chosing
	// pkp.registry.initVue('[data-eidos-vue-root]');
}

export default {
  init
}