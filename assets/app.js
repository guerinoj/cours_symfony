import "./bootstrap.js";
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import "./styles/app.css";
import "@fortawesome/fontawesome-free/css/fontawesome.min.css";
import "@fortawesome/fontawesome-free/css/solid.min.css";
import "@fortawesome/fontawesome-free/css/regular.min.css";

// Import conditionnel des scripts spécifiques aux pages
// Les scripts de pages seront chargés uniquement quand nécessaire

console.log("This log comes from assets/app.js - welcome to AssetMapper! 🎉");
