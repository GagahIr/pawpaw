// import { HSStaticMethods } from "preline/non-auto";

// function autoInit() {
//   HSStaticMethods.autoInit();
// }

// // First load and every wire:navigate visit
// document.addEventListener("livewire:navigated", autoInit);

// // After a component morphs its DOM
// document.addEventListener("livewire:init", () => {
//   Livewire.hook("morphed", () => {
//     autoInit();
//   });
// });

import { HSStaticMethods } from "preline/non-auto";
import './pawpaw-animations';

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
        HSStaticMethods.autoInit();
    });
} else {
    HSStaticMethods.autoInit();
}
