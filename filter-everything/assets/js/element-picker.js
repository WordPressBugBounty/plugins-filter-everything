(function ($) {
    "use strict";

    if (!window.ElementPicker?.ElementPicker) {
        console.warn("Element Picker has not loaded yet. Please try again in a second.");
        return;
    }

    const SelectorUtils = {

        isUnique(selector) {
            try {
                return document.querySelectorAll(selector).length === 1;
            } catch {
                return false;
            }
        },

        /**
         * Returns the exact selector of one element relative to its parent.
         * Format: tag.first-class:nth-of-type(N) or #id
         * nth-of-type is always added if there are identical tags among siblings —
         * this ensures precise targeting on any page.
         */
        getSegment(el) {
            // ID — the most precise and always unique
            if (el.id) {
                return "#" + CSS.escape(el.id);
            }

            const parent = el.parentElement;
            const tag = el.tagName.toLowerCase();

            // Get the first class (the most stable identifier)
            const rawClass = typeof el.className === "string"
                ? el.className.trim().split(/\s+/)[0]
                : null;
            const cls = rawClass ? "." + CSS.escape(rawClass) : "";

            // nth-of-type is calculated by tag (CSS specification)
            const sameTagSiblings = parent
                ? Array.from(parent.children).filter(s => s.tagName === el.tagName)
                : [el];

            const needsNth = sameTagSiblings.length > 1;
            const nthIndex = sameTagSiblings.indexOf(el) + 1;
            const nth = needsNth ? `:nth-of-type(${nthIndex})` : "";

            return `${tag}${cls}${nth}`;
        },

        /**
         * Builds the FULL path from <body> to the element.
         * Each segment is precise — contains nth-of-type when needed.
         * Result: guaranteed to be unique on any page.
         */
        buildFullPath(el) {
            const segments = [];
            let node = el;

            while (node && node !== document.documentElement) {
                if (node === document.body) {
                    segments.unshift("body");
                    break;
                }
                segments.unshift(this.getSegment(node));
                node = node.parentElement;
            }

            return segments.join(" > ");
        },

        shortenPath(fullPath) {
            const parts = fullPath.split(" > ");
            const lastPart = parts[parts.length - 1];

            //Try just the element itself
            if (this.isUnique(lastPart)) {
                return lastPart;
            }

            //Try element with simplified selectors (without nth-of-type if possible)
            const simplifiedLast = this.simplifySegment(lastPart);
            if (simplifiedLast !== lastPart && this.isUnique(simplifiedLast)) {
                return simplifiedLast;
            }

            //Try adding parents one by one from bottom to top
            for (let i = parts.length - 2; i >= 0; i--) {
                const candidate = parts.slice(i).join(" > ");
                if (this.isUnique(candidate)) {
                    return candidate;
                }
            }

            // Fallback: full path — always unique
            return fullPath;
        },

        /**
         * Simplifies a segment by removing nth-of-type if the element is unique without it
         */
        simplifySegment(segment) {
            // Remove nth-of-type
            return segment.replace(/:nth-of-type\(\d+\)$/, '');
        },
    };

    /**
     * Main function.
     * Returns { selector, type } — a guaranteed unique selector.
     */
    function generateUniqueSelector(element) {
        // Divi: if click inside .et_pb_ajax_pagination_container — take the container
        const isDivi = document.body.classList.contains("wp-theme-Divi") ||
            document.body.classList.contains("theme-Divi");

        if (isDivi) {
            let node = element;
            while (node && node !== document.body) {
                if (node.classList?.contains("et_pb_ajax_pagination_container")) {
                    element = node;
                    break;
                }
                node = node.parentElement;
            }
        }

        // Fast: unique ID
        if (element.id) {
            const sel = "#" + CSS.escape(element.id);
            if (SelectorUtils.isUnique(sel)) {
                return { selector: sel, type: "id" };
            }
        }

        // Build full path and shorten
        const full = SelectorUtils.buildFullPath(element);
        const selector = SelectorUtils.shortenPath(full);

        const type = selector.startsWith("#") ? "id"
            : selector.includes(".")    ? "class"
                : "tag";

        return { selector, type };
    }

    const UI = {
        $infoPanel: null,

        init() {
            this.$infoPanel = $("#wpc-choose-selector");
        },

        showSelector(selectorData) {
            $(".wpc-choose-block").hide();

            const typeMap = {
                id:    { el: "#wpc-choose-id-css",    display: "#wpc-chooses-html-id" },
                class: { el: "#wpc-choose-class-css", display: "#wpc-chooses-html-css" },
                tag:   { el: "#wpc-choose-class-css", display: "#wpc-chooses-html-css" },
            };

            const config = typeMap[selectorData.type];
            if (config) {
                $(config.el).show();
                $(config.display).val(selectorData.selector);
            } else {
                $("#wpc-choose-empty").show();
            }

            this.$infoPanel.show();
        },

        storeSelection(selectorData) {
            this.$infoPanel.data("selectedElement", {
                selector:  selectorData.selector,
                type:      selectorData.type,
                id:        selectorData.type === "id" ? selectorData.selector : "",
                className: selectorData.type !== "id" ? selectorData.selector : "",
            });
        },

        resetPanel() {
            this.$infoPanel.hide().removeData("selectedElement");
            $(".wpc-choose-block").hide();
        },

        getSelection() {
            return this.$infoPanel.data("selectedElement");
        },

        getSetId() {
            return this.$infoPanel.data("setId");
        },
    };

    function saveElementSelector(elementData, setId, $button) {
        const elementSelector = elementData.id || elementData.className;

        $button.prop("disabled", true).text(wpcElementPickerData.saving);

        window.parent.postMessage(
            { type: "SELECTOR_CHOSEN", elementSelector },
            "*"
        );
        window.close();
    }

    function isInsideInfoPanel(element) {
        return (
            element.id === "wpc-choose-selector" ||
            $(element).closest("#wpc-choose-selector").length > 0
        );
    }

    function initPicker() {
        return new window.ElementPicker.ElementPicker({
            picking: true,

            onClick(element, event) {
                if (isInsideInfoPanel(element)) return;

                const selectorData = generateUniqueSelector(element);
                UI.storeSelection(selectorData);
                UI.showSelector(selectorData);

                event.stopPropagation();
                this.stopPicking();
                $("#wpc-choose-another-selector").show();
            },

            onTargetChange(element) {
                if (isInsideInfoPanel(element)) return;

                const selectorData = generateUniqueSelector(element);
                UI.storeSelection(selectorData);
                UI.showSelector(selectorData);
            },

            overlayDrawer() {
                const overlay = document.createElement("div");
                Object.assign(overlay.style, {
                    width:      "100%",
                    height:     "100%",
                    background: "rgba(102, 126, 234, 0.2)",
                    border:     "3px solid #667eea",
                    boxSizing:  "border-box",
                });
                return overlay;
            },
        });
    }

    UI.init();
    initPicker();

    $(document).on("click", "#wpc-choose-another-selector", function () {
        $("#wpc-choose-another-selector").hide();
        initPicker();
    });

    $(document).on("click", "#wpc-choose-save-btn", function (e) {
        e.preventDefault();

        const elementData = UI.getSelection();
        if (!elementData) {
            alert(wpcElementPickerData.no_element_selected);
            return;
        }

        saveElementSelector(elementData, UI.getSetId(), $(this));
    });

})(jQuery);