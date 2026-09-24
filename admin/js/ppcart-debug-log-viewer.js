(function() {
    var buttons = document.querySelectorAll("[data-ppcart-debug-details]");
    buttons.forEach(function(button) {
        button.addEventListener("click", function() {
            var target = document.getElementById(button.getAttribute("data-ppcart-debug-details"));
            if (!target) {
                return;
            }

            var isOpen = target.classList.toggle("is-open");
            target.hidden = !isOpen;
            button.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });
    });

    var groupToggles = document.querySelectorAll("[data-ppcart-debug-group-toggle]");
    groupToggles.forEach(function(button) {
        button.addEventListener("click", function() {
            var card = button.closest(".ppcart-debug-log-group-card");
            var isExpanded = card ? card.classList.toggle("is-expanded") : false;
            button.setAttribute("aria-expanded", isExpanded ? "true" : "false");
        });
    });

    function selectEvent(eventId) {
        if (!eventId) {
            return;
        }

        var panel = document.querySelector("[data-ppcart-debug-panel=\"" + eventId + "\"]");
        if (!panel) {
            return;
        }

        var splitWrap = panel.closest(".ppcart-debug-log-split-wrap");
        if (splitWrap) {
            splitWrap.classList.remove("is-inspector-collapsed");
        }

        var eventButton = document.querySelector(".ppcart-debug-log-event-card[data-ppcart-debug-event=\"" + eventId + "\"]");
        if (eventButton) {
            var groupCard = eventButton.closest(".ppcart-debug-log-group-card");
            var groupToggle = groupCard ? groupCard.querySelector("[data-ppcart-debug-group-toggle]") : null;
            if (groupCard) {
                groupCard.classList.add("is-expanded");
            }
            if (groupToggle) {
                groupToggle.setAttribute("aria-expanded", "true");
            }
        }

        document.querySelectorAll("[data-ppcart-debug-panel]").forEach(function(item) {
            var isActive = item === panel;
            item.hidden = !isActive;
            item.classList.toggle("is-active", isActive);
        });

        document.querySelectorAll("[data-ppcart-debug-event]").forEach(function(button) {
            if (button.hasAttribute("disabled")) {
                return;
            }

            button.classList.toggle("is-active", button.getAttribute("data-ppcart-debug-event") === eventId);
        });
    }

    document.querySelectorAll("[data-ppcart-debug-event]").forEach(function(button) {
        button.addEventListener("click", function(event) {
            var eventId = button.getAttribute("data-ppcart-debug-event");
            if (!eventId) {
                return;
            }

            event.stopPropagation();
            selectEvent(eventId);
        });
    });

    document.querySelectorAll("[data-ppcart-debug-back]").forEach(function(button) {
        button.addEventListener("click", function() {
            var panel = button.closest("[data-ppcart-debug-panel]");
            var groupId = panel ? panel.getAttribute("data-ppcart-debug-group") : "";
            var timeline = groupId ? document.getElementById(groupId) : null;
            var groupCard = timeline ? timeline.closest(".ppcart-debug-log-group-card") : document.querySelector(".ppcart-debug-log-group-card.is-expanded");
            var groupToggle = groupCard ? groupCard.querySelector("[data-ppcart-debug-group-toggle]") : null;
            var splitWrap = button.closest(".ppcart-debug-log-split-wrap");

            if (groupCard) {
                groupCard.classList.remove("is-expanded");
            }
            if (groupToggle) {
                groupToggle.setAttribute("aria-expanded", "false");
                groupToggle.focus({ preventScroll: true });
            }
            if (groupCard && groupCard.scrollIntoView) {
                groupCard.scrollIntoView({ block: "nearest", behavior: "smooth" });
            }

            document.querySelectorAll("[data-ppcart-debug-panel]").forEach(function(item) {
                item.hidden = true;
                item.classList.remove("is-active");
            });
            document.querySelectorAll(".ppcart-debug-log-event-card.is-active").forEach(function(eventButton) {
                eventButton.classList.remove("is-active");
            });
            if (splitWrap) {
                splitWrap.classList.add("is-inspector-collapsed");
            }
        });
    });
})();
