(function() {
	var buttons = document.querySelectorAll("[data-ppcart-webhook-details]");

	buttons.forEach(function(button) {
		button.addEventListener("click", function() {
			var target = document.getElementById(button.getAttribute("data-ppcart-webhook-details"));

			if (!target) {
				return;
			}

			var isOpen = target.classList.toggle("is-open");
			target.hidden = !isOpen;
			button.setAttribute("aria-expanded", isOpen ? "true" : "false");
		});
	});
})();
