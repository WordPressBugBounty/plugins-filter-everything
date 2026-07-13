/*!
 * js-element-picker v1.0.1
 * JavaScript and TypeScript library for selecting elements on a web page.
 *
 * @author maxion03
 * @license ISC
 * @repository https://github.com/MaxioN03/js-element-picker
 *
 * Bundled with Browserify
 */
(function(f){if(typeof exports==="object"&&typeof module!=="undefined"){module.exports=f()}else if(typeof define==="function"&&define.amd){define([],f)}else{var g;if(typeof window!=="undefined"){g=window}else if(typeof global!=="undefined"){g=global}else if(typeof self!=="undefined"){g=self}else{g=this}g.ElementPicker = f()}})(function(){var define,module,exports;return (function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.ElementPicker = void 0;
const WrapperDrawer_1 = require("./WrapperDrawer");
class ElementPicker {
    constructor(props) {
        this.initialized = false;
        this.previousTarget = null;
        this.wrapperDrawer = null;
        this.container = null;
        this.onTargetChange = null;
        this.onClick = null;
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.initialize(props);
            });
        }
        else {
            this.initialize(props);
        }
    }
    initialize(props) {
        const { picking, container, overlayDrawer, onTargetChange, onClick } = props !== null && props !== void 0 ? props : {};
        this.container = container !== null && container !== void 0 ? container : document;
        this.wrapperDrawer = new WrapperDrawer_1.WrapperDrawer(overlayDrawer);
        if (onTargetChange) {
            this.onTargetChange = onTargetChange;
        }
        if (onClick) {
            this.onClick = onClick;
        }
        this.handleMouseMove = this.handleMouseMove.bind(this);
        this.handleClick = this.handleClick.bind(this);
        if (picking) {
            this.startPicking();
        }
        this.initialized = true;
    }
    handleMouseMove(event) {
        var _a, _b, _c;
        const target = event.target;
        const { x, y, width, height } = target === null || target === void 0 ? void 0 : target.getBoundingClientRect();
        if (target !== this.previousTarget) {
            if (!this.checkElementIfOddGlobal(target)) {
                (_a = this.wrapperDrawer) === null || _a === void 0 ? void 0 : _a.draw({ x, y, width, height }, event);
                (_b = this.onTargetChange) === null || _b === void 0 ? void 0 : _b.call(this, target, event);
            }
            else {
                (_c = this.wrapperDrawer) === null || _c === void 0 ? void 0 : _c.draw(null, null);
            }
            this.previousTarget = target;
        }
    }
    handleClick(event) {
        var _a;
        event.stopPropagation();
        event.preventDefault();
        (_a = this.onClick) === null || _a === void 0 ? void 0 : _a.call(this, event.target, event);
    }
    waitForInitialization() {
        const CHECK_INTERVAl = 100;
        return new Promise((resolve) => {
            if (this.initialized) {
                resolve(true);
            }
            else {
                const waitForInitializedInterval = setInterval(() => {
                    if (this.initialized) {
                        clearInterval(waitForInitializedInterval);
                        resolve(true);
                    }
                }, CHECK_INTERVAl);
            }
        });
    }
    checkElementIfOddGlobal(element) {
        return element === document.documentElement || element === document.body;
    }
    startPicking() {
        return __awaiter(this, void 0, void 0, function* () {
            yield this.waitForInitialization();
            const container = this.container;
            container.addEventListener('click', this.handleClick, false);
            container.addEventListener('mousemove', this.handleMouseMove, false);
        });
    }
    stopPicking() {
        return __awaiter(this, void 0, void 0, function* () {
            var _a;
            yield this.waitForInitialization();
            const container = this.container;
            container.removeEventListener('click', this.handleClick, false);
            container.removeEventListener('mousemove', this.handleMouseMove, false);
            (_a = this.wrapperDrawer) === null || _a === void 0 ? void 0 : _a.draw(null, null);
        });
    }
}
exports.ElementPicker = ElementPicker;

},{"./WrapperDrawer":2}],2:[function(require,module,exports){
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.WrapperDrawer = void 0;
class WrapperDrawer {
    constructor(overlayDrawer) {
        this.wrapper = null;
        this.overlayDrawer = null;
        this.defaultOverlayDrawer = () => {
            const defaultOverlay = document.createElement('div');
            defaultOverlay.setAttribute('data-testid', 'default-overlay');
            defaultOverlay.style.width = '100%';
            defaultOverlay.style.height = '100%';
            defaultOverlay.style.background = 'rgba(0, 0, 255, 0.5)';
            return defaultOverlay;
        };
        this.initialize();
        document.body.appendChild(this.wrapper);
        this.overlayDrawer = overlayDrawer !== null && overlayDrawer !== void 0 ? overlayDrawer : this.defaultOverlayDrawer;
    }
    initialize() {
        this.wrapper = document.createElement('div');
        this.wrapper.style.position = 'fixed';
        this.wrapper.style.display = 'none';
        this.wrapper.style.pointerEvents = 'none';
        this.wrapper.style.zIndex = '99999';
    }
    draw(position, event) {
        var _a;
        if (!this.wrapper) {
            return;
        }
        this.wrapper.innerHTML = '';
        const overlay = (_a = this.overlayDrawer) === null || _a === void 0 ? void 0 : _a.call(this, position, event);
        this.wrapper.append(overlay);
        if (position) {
            const { x, y, width, height } = position;
            this.wrapper.style.left = `${x}px`;
            this.wrapper.style.top = `${y}px`;
            this.wrapper.style.width = `${width}px`;
            this.wrapper.style.height = `${height}px`;
            this.wrapper.style.display = 'block';
        }
        else {
            this.wrapper.style.display = 'none';
        }
    }
}
exports.WrapperDrawer = WrapperDrawer;

},{}],3:[function(require,module,exports){
"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ElementPicker = void 0;
const ElementPicker_1 = require("./ElementPicker");
Object.defineProperty(exports, "ElementPicker", { enumerable: true, get: function () { return ElementPicker_1.ElementPicker; } });

},{"./ElementPicker":1}]},{},[3])(3)
});
