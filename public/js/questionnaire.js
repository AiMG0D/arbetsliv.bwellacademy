(function () {
    "use strict";

    $(document).ready(function () {
        $("li").delegate('input[type="text"]', "change", function () {
            var $this = $(this);
            var slide = $this.parent().parent().find(".help");
            if (slide.length > 0) {
                $(".help-button").removeClass("active");
                $this.parent().parent().find(".help").slideUp();
            }
            var name = $this.attr("name");
            var value = $this.val();
            var data = {
                name: name,
                value: value,
            };
            sendValue(data, $this);
        });

        $("li").delegate("select", "change", function () {
            var $this = $(this);
            var name = $this.attr("name");
            var value = $this.val();
            var data = {
                name: name,
                value: value,
            };
            sendValue(data, $this);
        });

        $("li").delegate('input[type="number"]', "change", function () {
            var $this = $(this);
            var name = $this.attr("name");
            var value = $this.val();
            var data = {
                name: name,
                value: value,
            };
            sendValue(data, $this);
        });

        $("li").delegate('input[type="radio"]', "click", function () {
            var $this = $(this);
            var name = $this.attr("name");
            var value = $this.val();
            var data = {
                name: name,
                value: value,
            };
            sendValue(data, $this);
        });

        $(".conditional").delegate('input[type="radio"]', "click", function () {
            var value = $(this).val();
            var toggle_value = $(this).data("toggle");
            var toggle = false;
            if (toggle_value) {
                if (toggle_value >= value) {
                    toggle = true;
                }
            } else {
                toggle = value == 1;
            }

            var question = $(this).parent().parent().parent();
            var elems = [];
            var searching = true;
            var e = $(question.next("li").get(0));
            while (searching) {
                if (e.hasClass("conditional-part")) elems.push($(e));
                else searching = false;

                e = $(e.next().get(0));
            }

            if (toggle) {
                if (question.hasClass("parent-question")) {
                    question.data("was-parent", true);
                } else {
                    question.data("was-parent", false);
                    question.removeClass("question");
                    question.addClass("parent-question");
                }

                $.each(elems, function (i, e) {
                    e.slideDown(400);
                });
            } else {
                if (question.data("was-parent")) {
                } else {
                    question.removeClass("parent-question");
                    question.addClass("question");
                }

                $.each(elems, function (i, e) {
                    e.slideUp(400);
                });
            }

            setTimeout(function () {
                updateHeights();
            }, 405);
        });

        $(".conditional").each(function () {
            var $this = $(this);
            var source = $this.find("input:checked");
            var value = source.val();
            var toggle_value = source.data("toggle");
            var toggle = false;
            if (toggle_value) {
                if (toggle_value >= value) {
                    toggle = true;
                }
            } else {
                toggle = value == 1;
            }

            var searching = true;
            var e = $($this.next("li").get(0));
            while (searching) {
                if (e.hasClass("conditional-part")) {
                    e.hide();

                    if (toggle) {
                        e.show();

                        if ($this.hasClass("question")) {
                            $this.data("was-parent", false);
                            $this.removeClass("question");
                            $this.addClass("parent-question");
                        } else {
                            $this.data("was-parent", true);
                        }
                    }
                } else {
                    e.show();
                    searching = false;
                }

                e = $(e.next("li").get(0));
            }
        });

        $(".improve-question input[type=radio]").click(function () {
            var radio = $(this);
            var category_id = radio.data("category-id");
            var value = radio.val();
            sendImproveValue(category_id, value);
        });

        $(".help-button").click(function () {
            var e = $(this);
            if (e.hasClass("active")) {
                $(".help-button").removeClass("active");
                $(".help").slideUp();
            } else {
                $(".help-button").removeClass("active");
                $(".help").slideUp();
                e.addClass("active");
                e.parents("li")
                    .find(".help")
                    .slideDown(400, function () {
                        updateHeights();
                    });
            }
        });

        $(".show-image-popup").magnificPopup({
            type: "image",
            closeOnContentClick: true,
            mainClass: "mfp-img-mobile",
            image: {
                verticalFit: true,
            },
        });

        $('.open-popup-link').magnificPopup({
            type: "inline",
        });
    });

    window.sendValue = function (data, element) {
        // console.trace();
        if (!window.editable) {
            return;
        }

        var li = $(element).parents("li");
        var category_id = li.data("category-id");

        if (category_id) {
            var spinner = $("#spinner").html();
            $(".improve.category" + category_id + " .status").html(spinner);

            var category_id_n = $("li[data-category-id=" + category_id + "]")
                .length;

            // count questions answered in category
            var n_radio = $(
                "li[data-category-id=" + category_id + "] input:checked"
            ).filter(function () {
                var input = $(this);
                var li = input.parents("li");
                if (li.data("question") == "bodyWeightEst") {
                    return input.val() != "";
                }
                if (li.data("template") == "form-joint") {
                    return input.val() >= 0;
                }
                if (input.parent().hasClass("radio-2")) {
                    return input.val() >= 0;
                } else {
                    return input.val() > 0;
                }
            }).length;

            var n_text = $(
                "li[data-category-id=" + category_id + "] input[type=text]"
            ).filter(function () {
                var input = $(this);
                return input.val() != "";
            }).length;

            var n_input = n_radio + n_text;

            var slide = false;
            // all questions in category answered
            switch (category_id) {
                case 4:
                    slide = true;
                    break;
                case 62:
                    slide = true;
                    break;
                default:
                    if (n_input == category_id_n) {
                        slide = true;
                    }
            }
            if (slide) {
                $(".improve.category" + category_id).slideDown(400);
            } else {
                $(".improve.category" + category_id).slideUp(400);
            }
        }

        var url = fms_url + "/profile/" + window.profile_id + "/value";

        $.ajax(url, {
            data: data,
            type: "POST",

            success: function (d, status, jqHXR) {
                if (typeof d.energy_intake_value !== "undefined") {
                    $(".energy-intake-value").html(d.energy_intake_value);
                }
                if (typeof d.energy_balance_value !== "undefined") {
                    var value = parseInt(d.energy_balance_value);
                    if (value >= 0) {
                        $(".energy-balance-value").html('+' + d.energy_balance_value);
                    } else {
                        $(".energy-balance-value").html(d.energy_balance_value);
                    }
                }
                if (typeof d.fitness_text !== "undefined") {
                    $(".fitness-text").html(d.fitness_text);
                }
                if (typeof d.status_text !== "undefined") {
                    $(".improve.category" + d.category_id + " .status").html(
                        d.status_text
                    );
                }
            },
        });
    };

    window.sendImproveValue = function (category_id, value) {
        // console.trace();
        if (!window.editable) {
            return;
        }

        var url = fms_url + "/statement/" + window.profile_id + "/improve";

        $.ajax(url, {
            data: {
                category_id: category_id,
                value: value,
            },
            type: "POST",

            success: function (d, status, jqHXR) {
                // console.log(d);
            },
        });
    };
})();

// Frontend translation for Swedish answer options
(function() {
    // Swedish to English translation mapping for common answer options
    const swedishToEnglish = {
        'Mycket bra': 'Very good',
        'Ganska bra': 'Quite good', 
        'Medelbra': 'Average',
        'Inte så bra': 'Not so good',
        'Dålig': 'Poor',
        'Ja, nästan alltid': 'Yes, almost always',
        'Ja, oftast': 'Yes, usually',
        'Ja, ganska ofta': 'Yes, quite often',
        'Nej, sällan': 'No, rarely',
        'Nej, nästan aldrig': 'No, almost never',
        'Ja': 'Yes',
        'Nej': 'No',
        'Aldrig': 'Never',
        'Sällan': 'Rarely',
        'Ibland': 'Sometimes',
        'Ofta': 'Often',
        'Mycket ofta': 'Very often',
        'Alltid': 'Always',
        'Inget svar': 'No answer'
    };

    // Function to translate Swedish text to English
    function translateSwedishToEnglish(text) {
        if (typeof text !== 'string') return text;
        
        // Check if we're in English mode
        const isEnglish = document.documentElement.lang === 'en' || 
                         window.location.pathname.includes('/en') ||
                         document.querySelector('html[lang="en"]');
        
        if (!isEnglish) return text;
        
        // Check if text is Swedish (contains Swedish characters or known Swedish words)
        const swedishPattern = /[åäöÅÄÖ]/;
        const isSwedish = swedishPattern.test(text) || swedishToEnglish.hasOwnProperty(text);
        
        if (isSwedish) {
            return swedishToEnglish[text] || text;
        }
        
        return text;
    }

    // Function to translate all labels on the page
    function translateLabels() {
        const labels = document.querySelectorAll('label');
        labels.forEach(label => {
            const originalText = label.textContent.trim();
            const translatedText = translateSwedishToEnglish(originalText);
            if (translatedText !== originalText) {
                label.textContent = translatedText;
            }
        });
    }

    // Run translation when page loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', translateLabels);
    } else {
        translateLabels();
    }

    // Also run translation when content changes (for dynamic content)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                translateLabels();
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
})();
