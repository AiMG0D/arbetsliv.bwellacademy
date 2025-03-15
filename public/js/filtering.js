(function () {
    "use strict";

    var RISK = 1;
    var HEALTHY = 2;
    var UNKNOWN = 4;

    window.FADE_SPEED = 200;

    //window.disabledConstraints = [];

    var removeButton = $(document.createElement("span"));
    removeButton.addClass("remove-button");
    var removeLink = $(document.createElement("a"));
    removeLink.attr("href", "#remove-cr");
    removeLink.html("X");
    removeButton.append(removeLink);

    $("#range-option").click(function () {
        var $this = $(this);
        var range = $("#range");
        var val = range.val();

        var country = $("#country-option");
        var county = $("#county-option");
        var schoolType = $("#school-type-option");
        var programme = $("#programme-option");
        var classOption = $("#class-option");

        if ($this.hasClass("opened")) {
            if (val == 1) {
                country.data("in-use", "yes");
                county.data("in-use", "yes");
                schoolType.data("in-use", "yes");

                classOption.data("in-use", "no");

                country.slideDown();
                county.slideDown();
                schoolType.slideDown();

                classOption.slideUp();
                if (programme.data("used") == "yes") {
                    programme.data("in-use", "no");
                    programme.slideUp();
                }
            } else {
                country.slideUp();
                county.slideUp();
                schoolType.slideUp();

                classOption.slideDown();

                country.data("in-use", "no");
                county.data("in-use", "no");
                schoolType.data("in-use", "no");

                classOption.data("in-use", "yes");

                if (programme.data("used") == "yes") {
                    programme.data("in-use", "yes");
                    programme.slideDown();
                }
            }
        } else {
            country.data("in-use", "no");
            county.data("in-use", "no");
            schoolType.data("in-use", "no");

            classOption.data("in-use", "yes");

            country.slideUp();
            county.slideUp();
            schoolType.slideUp();

            classOption.slideDown();

            if (programme.data("used") == "yes") {
                programme.data("in-use", "yes");
                programme.slideDown();
            }
        }
    });

    $("#range").change(function () {
        var $this = $(this);
        var selected = $this.val();

        var country = $("#country-option");
        var county = $("#county-option");
        var schoolType = $("#school-type-option");
        var programme = $("#programme-option");
        var classOption = $("#class-option");
        var businessCategory = $('#business-category-option');

        if (selected == 0) {
            if (window.fms_type == "work") {
                if (window.fms_lang == "en") {
                    $(".selection-title").html("Company");
                } else {
                    $(".selection-title").html("Företaget");
                }
            } else {
                if (window.fms_lang == "en") {
                    $(".selection-title").html("School");
                } else {
                    $(".selection-title").html("Skolan");
                }
            }

            country.data("in-use", "no");
            county.data("in-use", "no");
            schoolType.data("in-use", "no");

            classOption.data("in-use", "yes");

            country.slideUp();
            county.slideUp();
            schoolType.slideUp();

            classOption.slideDown();

            if (programme.data("used") == "yes") {
                programme.data("in-use", "yes");
                programme.slideDown();
            }
        } else {
            $(".selection-title").html("Större räckvidd");
            // console.log($(".selection-title").html());

            country.data("in-use", "yes");
            county.data("in-use", "yes");
            schoolType.data("in-use", "yes");

            classOption.data("in-use", "no");

            country.slideDown();
            county.slideDown();
            schoolType.slideDown();

            classOption.slideUp();

            if (programme.data("used") == "yes") {
                programme.data("in-use", "no");
                programme.slideUp();
            }
        }
    });

    function copyFilters() {
        var compareContents = $("#compare-contents");
    }

    window.serializeFilter = function () {
        var output = [];
        var checkedName = "";
        var tempCheck = [];

        function serializeElement(eIdx, element) {
            var $elem = $(element),
                name = $elem.attr("id"),
                type = element.tagName.toLowerCase(),
                extraType = $elem.attr("type"),
                extraName = $elem.attr("name"),
                value = $elem.val(),
                valueLabel = "",
                nameLabel = $elem
                    .parent()
                    .parent()
                    .children(0)
                    .children(0)
                    .html(),
                hasValue = true;
            if (type == "input") {
                if (value === "") {
                    hasValue = false;
                }

                if (extraType === "checkbox") {
                    if (!$elem.prop("checked")) return;

                    var n = extraName.split(".");
                    checkedName = n[0];
                    tempCheck.push(n[1]);
                    hasValue = false;
                }
            } else if (type == "select") {
                if (name == "section") {
                    if (value == 0 || value.indexOf(".0") > -1) {
                        if (window.fms_type == "work") {
                            if (window.fms_lang == "en") {
                                $(".selection-title").html("Company");
                            } else {
                                $(".selection-title").html("Företaget");
                            }
                        } else {
                            if (window.fms_lang == "en") {
                                $(".selection-title").html("School");
                            } else {
                                $(".selection-title").html("Skolan");
                            }
                        }
                    } else {
                        if (window.fms_type == "work") {
                            if (window.fms_lang == "en") {
                                $(".selection-title").html("Section");
                            } else {
                                $(".selection-title").html("Avdelning");
                            }
                        } else {
                            if (window.fms_lang == "en") {
                                $(".selection-title").html("Class");
                            } else {
                                $(".selection-title").html("Klass");
                            }
                        }
                    }
                }

                if (name == "programme-all" || name == "programme-school") {
                    name = "programme";

                    if (!$elem.data("in-use")) {
                        hasValue = false;
                    }
                }

                valueLabel = $("option[value='" + value + "']", $elem).html();
            } else if (type == "div") {
                $elem.children().each(serializeElement);
                return;
            } else {
                return;
            }

            if (hasValue) {
                output.push({
                    name: name,
                    nameLabel: nameLabel,
                    type: type,
                    value: value,
                    valueLabel: valueLabel,
                });
            }
        }

        var filterTypeValue = $("input[name='filter-type']:checked").val();
        var countryOption = $("#country-option");

        // Fix för att visa globala resultat utan att välja det.
        if (
            filterTypeValue === "globalt" &&
            !countryOption.hasClass("opened")
        ) {
            output.push({
                name: "country",
                nameLabel: "",
                type: "",
                value: 0,
                valueLabel: "",
            });
        }

        // serialize
        $(".opened").each(function (optIdx, option) {
            var $option = $(option);

            var elements = $option.find(".elements").children();

            var checked = [];

            elements.each(serializeElement);
            if (tempCheck.length > 0) {
                // var length = checked.length;
                // for (var i = 0; i < length; i++)
                // {
                //     var check = checked[i];
                // };

                output.push({
                    name: checkedName,
                    type: "checkboxes",
                    value: tempCheck.join("|"),
                });
                tempCheck = [];
            }
        });

        var elements = $(".opened .elements .categories");
        var categories = [];
        elements.children().each(function (eIdx, element) {
            var $elem = $(element),
                value = $elem.data("value");

            categories.push(value);
        });

        if (categories.length > 0) {
            output.push({
                name: "categories",
                type: "li",
                value: categories.join("|"),
            });
        }

        elements = $(".opened .elements .constraints");
        var constraints = [];
        elements.children().each(function (eIdx, element) {
            var $elem = $(element),
                factor = $elem.data("name"),
                checked = $elem.data("checked");

            /*var flags = 0;
            checked = checked.split("|");
            for (var i = 0; i < checked.length; i++) {
                switch (checked[i])
                {
                    case "risk":
                        flags |= RISK;
                        break;
                    case "healthy":
                        flags |= HEALTHY;
                        break;
                    default:
                    case "unknown":
                        flags |= UNKNOWN;
                        break;
                }
            }*/

            constraints.push(factor + ":" + checked);
        });

        if (constraints.length > 0) {
            output.push({
                name: "constraints",
                nameLabel: "Begränsningar",
                type: "li",
                value: constraints.join("|"),
                // TODO: valueLabel
            });
        }

        if (
            !$("#class-option").hasClass("opened") &&
            !$("#range-option").hasClass("opened")
        ) {
            if (window.fms_type == "work") {
                if (window.fms_lang == "en") {
                    $(".selection-title").html("Company");
                } else {
                    $(".selection-title").html("Företaget");
                }
            } else {
                if (window.fms_lang == "en") {
                    $(".selection-title").html("School");
                } else {
                    $(".selection-title").html("Skolan");
                }
            }
        }

        // console.log(output);
        return output;
    };

    window.serializeFiltersForChart = function () {
        var output = [];
        var checkedName = "";
        var tempCheck = [];

        function serializeElement(eIdx, element) {
            var $elem = $(element),
                name = $elem.attr("id"),
                type = element.tagName.toLowerCase(),
                extraType = $elem.attr("type"),
                extraName = $elem.attr("name"),
                value = $elem.val(),
                valueLabel = "",
                nameLabel = $elem.parents(".filter-option").find("h5 a").html(),
                hasValue = true;

            if (type == "input") {
                if (value === "") {
                    hasValue = false;
                }

                if (extraType === "checkbox") {
                    if (!$elem.prop("checked")) return;

                    var n = extraName.split(".");
                    checkedName = n[0];
                    tempCheck.push(n[1]);
                    hasValue = false;
                }
            } else if (type == "select") {
                valueLabel = $("option[value='" + value + "']", $elem).html();
            } else {
                return;
            }

            if (hasValue) {
                return {
                    name: name,
                    nameLabel: nameLabel,
                    type: type,
                    value: value,
                    valueLabel: valueLabel,
                };
            } else {
                return null;
            }
        }

        // serialize
        $(".opened").each(function (optIdx, option) {
            var $option = $(option);

            var elements = $option.find(".elements").children();

            var checked = [];

            var elen = elements.length;
            if (elements.length > 1) {
                return;
            } else {
                output.push(serializeElement(0, elements[0]));
            }

            if (tempCheck.length > 0) {
                output.push({
                    name: checkedName,
                    type: "checkboxes",
                    value: tempCheck.join("|"),
                });

                tempCheck = [];
            }
        });

        // age
        var ageOption = $("#age-option.opened");
        if (ageOption.length > 0) {
            var ageElements = ageOption.find(".elements");
            var children = ageElements.children();
            var fromValue = parseInt($(children.get(0)).val());
            var toValue = parseInt($(children.get(1)).val());
            var valid = false;
            var valueLabel = "";

            if (
                !isNaN(fromValue) &&
                fromValue > 0 &&
                !isNaN(toValue) &&
                toValue > 0
            ) {
                valueLabel = "Mellan ";
                valueLabel += fromValue;
                valueLabel += " och ";
                valueLabel += toValue;
                valueLabel += " år";
                valid = true;
            } else if (!isNaN(fromValue) && fromValue > 0) {
                valueLabel = "Från " + fromValue + " år";
                valid = true;
            } else if (!isNaN(toValue) && toValue > 0) {
                valueLabel = "Till " + toValue + " år";
                valid = true;
            }

            if (valid) {
                output.push({
                    nameLabel: "Ålder",
                    valueLabel: valueLabel,
                });
            }
        }

        // grade
        var termOption = $("#term-option.opened");
        if (termOption.length > 0) {
            var termElements = termOption.find(".elements");
            var children = termElements.children();
            var valueLabel = "";

            var yearFromElement = termElements.find("#year-from");
            var yearToElement = termElements.find("#year-to");
            var semesterFromElement = termElements.find("#semester-from");
            var semesterToElement = termElements.find("#semester-to");

            var yearFromValue = parseInt(yearFromElement.val());
            var yearToValue = parseInt(yearToElement.val());
            var semesterFromValue = parseInt(semesterFromElement.val());
            var semesterToValue = parseInt(semesterToElement.val());

            var semesterFrom = "";
            var semesterTo = "";
            if (semesterFromValue > 0) {
                semesterFrom = semesterFromValue == 1 ? "VT" : "HT";
            }
            if (semesterToValue > 0) {
                semesterTo = semesterToValue == 1 ? "VT" : "HT";
            }

            if (yearFromValue > 0) {
                if (semesterFromValue > 0) {
                    valueLabel = semesterFrom + " " + yearFromValue;
                } else {
                    valueLabel = yearFromValue;
                }

                output.push({
                    nameLabel: "Termin, från",
                    valueLabel: valueLabel,
                });
            }

            if (yearToValue > 0) {
                if (semesterToValue > 0) {
                    valueLabel = semesterTo + " " + yearToValue;
                } else {
                    valueLabel = yearToValue;
                }

                output.push({
                    nameLabel: "Termin, till",
                    valueLabel: valueLabel,
                });
            }
        }

        var elements = $(".opened .elements .constraints");
        var constraints = [];
        elements.children().each(function (eIdx, element) {
            var $elem = $(element),
                factor = $elem.data("name"),
                checked = $elem.data("checked");

            constraints.push(factor + ":" + checked);
        });

        if (constraints.length > 0) {
            output.push({
                name: "constraints",
                nameLabel: "Begränsningar",
                type: "li",
                value: constraints.join("|"),
                // TODO: valueLabel
            });
        }

        // console.log(output);
        return output;
    };

    function updateFilter() {
        var filterOptions = serializeFilter();

        // ajax away the JSON
        $.ajax( "/statistics/filter/set", {
            data: filterOptions,
            dataType: "json",
            method: "post",
            success: function (response) {
                $(".spinner").fadeOut(250);

                if (response.length === 0 || response.numStudents == 0) {
                    $(".inner .active").fadeOut(FADE_SPEED, function () {
                        $(".inner .active").removeClass("active");

                        if (filterOptions.length == 0) {
                            $(".empty-school").fadeIn(400, function () {
                                $(".empty-school").addClass("active");
                                updateHeights();
                            });
                        } else {
                            var foptsLength = filterOptions.length;
                            for (var i = 0; i < foptsLength; i++) {
                                var opt = filterOptions[i];
                                if (
                                    opt["name"] == "section" &&
                                    opt["value"] == 0
                                ) {
                                    $(".empty-school").fadeIn(400, function () {
                                        $(".empty-school").addClass("active");
                                        updateHeights();
                                    });
                                    return;
                                }
                            }
                            $(".empty").fadeIn(400, function () {
                                $(".empty").addClass("active");
                                updateHeights();
                            });
                        }
                    });

                    return;
                }

                function showBars(response) {
                    doughnut(
                        response.numWomen,
                        response.numMen,
                        response.riskGroupMen,
                        response.riskGroupWomen
                    );

                    var pages = response.pages;

                    var labels = {};
                    var values = {};

                    var pagesLength = pages.length;
                    for (var i = 0; i < pagesLength; i++) {
                        var pageName = pages[i];
                        var pageLabels = response.mappedLabels[pageName];
                        var pageValues = response.mappedValues[pageName];

                        for (var name in pageLabels) {
                            labels[name] = pageLabels[name];
                        }

                        for (var name in pageValues) {
                            values[name] = pageValues[name];
                        }
                    }

                    // console.log(response.pages, labels, values, response.factorMap);

                    factorChart(
                        response.pages,
                        labels,
                        values,
                        response.factorMap
                    );
                }

                var ssplElem = $("#show-selection-people-link");
                var ssplVisible = false;
                if (response.cacheId != null) {
                    var destination =
                        "/statistics/selection/" + response.cacheId;
                    ssplElem.attr("href", destination);
                    ssplVisible = true;
                }

                if ($(".inner .single").hasClass("active")) {
                    if (ssplVisible) {
                        ssplElem.fadeIn();
                    } else {
                        ssplElem.fadeOut();
                    }

                    showBars(response);
                    return;
                }

                if (ssplVisible) {
                    ssplElem.css("display", "inline");
                } else {
                    ssplElem.css("display", "none");
                }

                $(".inner .active").fadeOut(FADE_SPEED, function (e) {
                    $(this).removeClass("active");

                    $(".inner .single").fadeIn(FADE_SPEED, function () {
                        $(".inner .single").addClass("active");
                        showBars(response);
                        updateHeights();
                    });
                });
            },
            beforeSend: function () {
                $(".spinner").fadeIn(250);
                return true;
            },
        });
    }

    function saveFilter(name, slug) {
        var output = serializeFilter();

        var data = {
            name: name,
            slug: slug,
            filter: output,
        };

        $.ajax(fms_url + "/statistics/filter/save", {
            data: data,
            dataType: "json",
            method: "post",
            success: function (response) {
                $(".spinner").fadeOut(250);
                if (response.status !== "ok") {
                    alert(
                        "Ett fel uppstod när filtret skulle sparas: " +
                            response.message
                    ); // TODO: ordentligt felmeddelande
                    return;
                }

                // Lägg till i egna filter listan under snabbval
                var parent1 = $(".user-list");

                var li = $(document.createElement("li"));
                li.data("filter", data.filter);
                li.data("slug", data.slug);

                var set = $(document.createElement("a"));
                set.attr("href", "javascript:;");
                set.addClass("set-filter");
                set.html(data.name);
                set.click(activateFilter);
                li.append(set);

                var rem = $(document.createElement("a"));
                rem.attr("href", "#remove-filter");
                rem.addClass("remove-filter-link");
                rem.addClass("remove-button");
                rem.click(removeFilter);
                rem.html("X");
                li.append(rem);

                parent1.append(li);

                // Lägg till i egna filter listan under jämför
                var parent2 = $("#compare-user-filters");

                var opt = $(document.createElement("option"));
                opt.data("filter", data.filter);
                opt.val(data.slug);
                opt.html(data.name);

                parent2.append(opt);

                parent2.get(0).sumo.reload();

                $.magnificPopup.close();
                $("#filter-name").val("");
                $("#filter-slug").val("");
            },
            beforeSend: function () {
                $(".spinner").fadeIn(250); // TODO: gör ordentlig
                return;
            },
        });
    }

    function setFilterOptions(filter) {
        var $parent = $("#options-contents");
        $parent.find(".opened").removeClass("opened");

        $.each(filter, function (idx, element) {
            var name = element.name;
            var type = element.type;
            var extra = element.extra;
            var value = element.value;

            var $el;
            //$el = $parent.find("#" + name);

            if (name == "programme") {
                $el = $parent.find(type + "[name=" + name + "]");
            } else {
                $el = $parent.find("#" + name);
            }

            if ($el.length === 0) {
                alert(
                    "Problem att ladda ditt urval, var god kontakta Bwell support"
                );
                return;
            }

            //$el.parent().parent().addClass("opened");
            $el.closest(".filter-option").addClass("opened");

            if (name == "range") {
                var country = $("#country-option");
                var county = $("#county-option");
                var schoolType = $("#school-type-option");
                var programme = $("#programme-option");
                var classOption = $("#class-option");

                if (value == 0) {
                    if (window.fms_type == "work") {
                        if (window.fms_lang == "en") {
                            $(".selection-title").html("Company");
                        } else {
                            $(".selection-title").html("Företaget");
                        }
                    } else {
                        if (window.fms_lang == "en") {
                            $(".selection-title").html("School");
                        } else {
                            $(".selection-title").html("Skolan");
                        }
                    }

                    country.data("in-use", "no");
                    county.data("in-use", "no");
                    schoolType.data("in-use", "no");

                    classOption.data("in-use", "yes");

                    country.slideUp();
                    county.slideUp();
                    schoolType.slideUp();

                    classOption.slideDown();

                    if (programme.data("used") == "yes") {
                        programme.data("in-use", "yes");
                        programme.slideDown();
                    }
                } else {
                    $(".selection-title").html("Större räckvidd");

                    country.data("in-use", "yes");
                    county.data("in-use", "yes");
                    schoolType.data("in-use", "yes");

                    classOption.data("in-use", "no");

                    country.slideDown();
                    county.slideDown();
                    schoolType.slideDown();

                    classOption.slideUp();

                    if (programme.data("used") == "yes") {
                        programme.data("in-use", "no");
                        programme.slideUp();
                    }
                }
            }

            if (type == "li") {
                var split = value.split("|");

                // Korskoppling och kategorier är specialfall.
                if (name == "categories") {
                    var $cat = $(".filter-cats");
                    $.each(split, function (i, e) {
                        var categoryDef = $cat.find("#cat-" + e);
                        categoryDef.prop("checked", true);

                        var category = $(document.createElement("li"));

                        category.data("value", e);
                        category.html(categoryDef.data("label"));

                        $el.append(category);
                    });
                } else {
                    var $crs = $(".cr-categories");
                    $.each(split, function (i, e) {
                        var facts = e.split(":");
                        var factor = facts[0];
                        var flags = parseInt(facts[1]);
                        var checks = facts[1].split(",");

                        var categoryDef = $("#cr-" + factor);
                        categoryDef.prop("disabled", true);
                        var label = categoryDef.html();

                        var li = $(document.createElement("li"));
                        li.data("name", factor);
                        li.html(label);

                        var checked = [];

                        var span;
                        var length = checks.length;
                        for (var i = 0; i < length; i++) {
                            var check = checks[i];

                            if (check === "unknown") {
                                checked.push("unknown");

                                span = $(document.createElement("span"));
                                span.addClass("cr-unknown");
                                span.html("Okänd");

                                li.append(span);
                                continue;
                            }

                            span = $(document.createElement("span"));

                            var checkValue = parseInt(check);
                            checked.push(check);
                            if (checkValue <= 2) {
                                span.addClass("cr-risk");
                            } else {
                                span.addClass("cr-healthy");
                            }
                            span.html(check);

                            li.append(span);
                        }

                        var rb = removeButton.clone();
                        rb.data("label", label);
                        rb.click(removeCR);
                        li.append(rb);

                        li.data("checked", checked.join(","));

                        $el.append(li);
                    });
                }

                return;
            } else {
                $el.val(value);
            }
        });

        updateFilter();
    }

    window.updateFilter = updateFilter;

    window.removeCR = function (e) {
        e.preventDefault();

        $(".factor-name").data("value", $(this).parent());
        $(".factor-name").html($(this).data("label"));

        $.magnificPopup.open(
            {
                items: {
                    src: "#remove-cr",
                },
                type: "inline",

                fixedContentPos: false,
                fixedBgPos: false,

                overflowY: "auto",

                closeBtnInside: true,
                preloader: false,

                midClick: true,
                removalDelay: 300,
                mainClass: "my-mfp-zoom-in",
            },
            0
        );
    };

    window.removeFilter = function (e) {
        e.preventDefault();

        var li = $(this).parent();
        $(".user-filter-name").data("value", li);
        $(".user-filter-name").html($(this).prev().html());

        $.magnificPopup.open(
            {
                items: {
                    src: "#remove-filter",
                },
                type: "inline",

                fixedContentPos: false,
                fixedBgPos: false,

                overflowY: "auto",

                closeBtnInside: true,
                preloader: false,

                midClick: true,
                removalDelay: 300,
                mainClass: "my-mfp-zoom-in",
            },
            0
        );
    };

    window.fetchFilteredResults = function (data, callback) {
        $.post(fms_url + "/statistics/filter/save", data, callback);
    };

    window.slug = function (str) {
        var $slug = "";
        var trimmed = $.trim(str);
        $slug = trimmed
            .replace(/[åä]/gi, "a")
            .replace(/ö/gi, "o")
            .replace(/[^a-z0-9-]/gi, "-")
            .replace(/-+/g, "-")
            .replace(/^-|-$/g, "");
        return $slug.toLowerCase();
    };

    $(".tabs li a").click(function () {
        var $this = $(this);
        var tabs = $(".tabs");
        var active = $(".sidepanel .active a", tabs);
        var tab = $this.data("tab");

        if (active.data("tab") === tab) {
            return;
        }

        $(".sidepanel .active, .active-tab")
            .removeClass("active")
            .removeClass("active-tab");
        $this.parent().addClass("active-tab");
        $("#" + tab + "-contents").addClass("active");
    });

    $(".filter-option h5 a").click(function () {
        var $this = $(this);
        var parent = $($this.parent().parent());
        var id = parent.attr("id");
        parent.toggleClass("opened");
        if (id == "country-option" || id == "county-option") {
            var programmeSchool = $("#programme-school");
            var programmeAll = $("#programme-all");
            if (parent.hasClass("opened")) {
                programmeSchool.addClass("hidden");
                programmeSchool.data("in-use", false);
                programmeAll.removeClass("hidden");
                programmeAll.data("in-use", true);
            } else {
                programmeSchool.removeClass("hidden");
                programmeSchool.data("in-use", true);
                programmeAll.addClass("hidden");
                programmeAll.data("in-use", false);
            }
        }
        if (!parent.hasClass("opened")) {
            //updateFilter();
        }
    });

    $(".compare-option h5 a").click(function () {
        var $this = $(this);
        var parent = $($this.parent().parent());
        parent.toggleClass("opened");
    });

    $(".filter-text").blur(function () {
        //updateFilter();
    });

    $(".filter-select").change(function () {
        //updateFilter();
    });

    $("#country").change(function () {
        if ($("#country").val() == 2) {
            $("#county-option").removeClass("opened").hide();
            $("#programme-option").removeClass("opened").hide();
        } else {
            $("#county-option").show();
            $("#programme-option").show();
        }
    });

    $("input[name='filter-type']").change(function () {
        $(".reset-filters").trigger("click");

        //var filterTypeValue = $('#filter-type').val();
        var filterTypeValue = $("input[name='filter-type']:checked").val();

        if (filterTypeValue === "globalt") {
            if (window.fms_lang == "en") {
                $(".selection-title").html("Global");
            } else {
                $(".selection-title").html("Globalt");
            }

            $('#business-category-option').show();
            $("#country-option").show();
            $("#county-option").show();
            $("#school-type-option").show();
            $("#class-option").hide();
            $("#sample-option").hide();

            // $('#country-option').addClass("opened");
            $("#class-option").removeClass("opened");

            var programmeSchool = $("#programme-school");
            var programmeAll = $("#programme-all");

            programmeSchool.addClass("hidden");
            programmeSchool.data("in-use", false);
            programmeAll.removeClass("hidden");
            programmeAll.data("in-use", true);
        } else {
            $('#business-category-option').hide();
            $("#country-option").hide();
            $("#county-option").hide();
            $("#school-type-option").hide();
            $("#class-option").show();
            $("#sample-option").show();

            $("#country-option").removeClass("opened");
            $("#county-option").removeClass("opened");
            $("#school-type-option").removeClass("opened");

            var programmeSchool = $("#programme-school");
            var programmeAll = $("#programme-all");

            programmeSchool.removeClass("hidden");
            programmeSchool.data("in-use", true);
            programmeAll.addClass("hidden");
            programmeAll.data("in-use", false);
        }
    });

    $('.elements input[type="checkbox"]').change(function () {
        //updateFilter();
    });

    $(".search-filter").click(function () {
        updateFilter();
    });

    $(".filter-option .open-choose-categories").magnificPopup({
        type: "inline",

        fixedContentPos: false,
        fixedBgPos: false,

        overflowY: "auto",

        closeBtnInside: true,
        preloader: false,

        midClick: true,
        removalDelay: 300,
        mainClass: "my-mfp-zoom-in",

        callbacks: {
            close: function () {
                var $elements = $("#choose-categories .filter-cats input"),
                    $target = $(".filter-categories .elements .categories");

                $target.empty();
                $elements.each(function (idx, el) {
                    var $el = $(el);
                    if (!$el.prop("checked")) return;
                    var li = $(document.createElement("li"));
                    li.data("value", $el.attr("id"));
                    li.html($el.data("label"));
                    $target.append(li);
                });

                updateFilter();
            },
        },
    });

    $("#choose-categories .save-categories").click(function (e) {
        e.preventDefault();
        $.magnificPopup.close();
    });

    $(".add-cross-reference").click(function (e) {
        e.preventDefault();

        var $checkboxes = $(".filter-cr input");
        var checked = false;
        $checkboxes.each(function (idx, el) {
            var $el = $(el);
            if (!$el.is(":checked")) return;

            checked = true;
        });

        if (checked) {
            $("#saving").val(1);
            $.magnificPopup.close();
        } else {
            $(".filter-cr .error").html(
                "Du måste kryssa i minst ett av valen."
            );
            return;
        }
    });

    $(".remove-cr-btn").click(function () {
        var el = $($(".factor-name").data("value").get(0));
        var selEl = $(
            '.filter-cr select option[value="' + el.data("name") + '"]'
        );
        selEl.prop("disabled", false);
        el.remove();
        $.magnificPopup.close();
        updateFilter();
    });

    $("#filter-name").keyup(function () {
        var text = $(this).val();
        $("#filter-slug").val(slug(text));
    });

    $(".open-save-filter").click(function (e) {
        e.preventDefault();

        var opened = $(".opened");
        if (opened.length === 0) {
            alert("Minst ett filter måste väljas för att spara ett urval."); // TODO: gör snyggare
            return;
        }

        $.magnificPopup.open({
            items: {
                src: "#save-filter",
            },
            type: "inline",

            fixedContentPos: false,
            fixedBgPos: false,

            overflowY: "auto",

            closeBtnInside: true,
            preloader: false,

            midClick: true,
            removalDelay: 300,
            mainClass: "my-mfp-zoom-in",
        });
    });

    $(".save-filter").click(function () {
        var name = $("#filter-name");
        var slug = $("#filter-slug");

        saveFilter(name.val(), slug.val());
    });

    $(".standard-list a").click(function () {
        var li = $(this).parent();
        var type = li.data("type");

        if (window.fms_type == "work") {
            if (window.fms_lang == "en") {
                $(".selection-title").html("Company");
            } else {
                $(".selection-title").html("Företaget");
            }
        } else {
            if (window.fms_lang == "en") {
                $(".selection-title").html("School");
            } else {
                $(".selection-title").html("Skolan");
            }
        }

        var filter = [];
        filter.push({
            name: "section",
            type: "select",
            value: 0,
        });

        setFilterOptions(filter);
    });

    $(".sample-list a").click(function () {
        var li = $(this).parent();

        var filter = [
            {
                name: "sample-group",
                type: "select",
                value: li.data("group-id"),
            },
        ];

        setFilterOptions(filter);
    });

    function activateFilter() {
        var li = $(this).parent();
        var filter = li.data("filter");

        var type = false;

        for (var key in filter) {
            if (
                filter[key].name === "country" ||
                filter[key].name === "county" ||
                filter[key].name === "school-type"
            ) {
                type = true;
            }
        }

        if (type === true) {
            $('input:radio[name="filter-type"]')
                .filter('[value="skolan"]')
                .prop("checked", false);
            $('input:radio[name="filter-type"]')
                .filter('[value="globalt"]')
                .prop("checked", true);
            $('input:radio[name="filter-type"]').trigger("change");
        } else {
            $('input:radio[name="filter-type"]')
                .filter('[value="globalt"]')
                .prop("checked", false);
            $('input:radio[name="filter-type"]')
                .filter('[value="skolan"]')
                .prop("checked", true);
            $('input:radio[name="filter-type"]').trigger("change");
        }

        setFilterOptions(filter);
    }

    $(".user-list .set-filter").click(activateFilter);

    $("#section").change(function () {
        $("#country-option").removeClass("opened");
    });

    $("#section-filter").change(function () {
        var $this = $(this);
        var id = $this.val();
        var filter = [];

        if (id == 0) {
            if (window.fms_type == "work") {
                if (window.fms_lang == "en") {
                    $(".selection-title").html("Company");
                } else {
                    $(".selection-title").html("Företaget");
                }
            } else {
                if (window.fms_lang == "en") {
                    $(".selection-title").html("School");
                } else {
                    $(".selection-title").html("Skolan");
                }
            }
        } else {
            if (window.fms_type == "work") {
                if (window.fms_lang == "en") {
                    $(".selection-title").html("Section");
                } else {
                    $(".selection-title").html("Avdelning");
                }
            } else {
                if (window.fms_lang == "en") {
                    $(".selection-title").html("Class");
                } else {
                    $(".selection-title").html("Klass");
                }
            }
        }

        filter.push({
            name: "section",
            type: "select",
            value: id,
        });

        setFilterOptions(filter);
    });

    $(".user-list .remove-filter-link").click(removeFilter);

    $(".remove-filter-btn").click(function () {
        var listItem = $(".user-filter-name").data("value");

        var data = {
            slug: listItem.data("slug"),
        };

        $.post(
            fms_url + "/statistics/filter/remove",
            data,
            function (response) {
                if (response.status != "ok") {
                    alert("Ett fel uppstod: " + response.message);
                } else {
                    $("#compare-user-filters option").each(function (i, e) {
                        var val = $(e).val();
                        if (val == data.slug) {
                            var parent = $(e).parent().get(0);
                            $(e).remove();

                            parent.sumo.reload();
                        }
                    });
                    listItem.remove();
                    $.magnificPopup.close();
                }
            },
            "json"
        );
    });

    $(".reset-filters").click(function (e) {
        e.preventDefault();

        var opened = $(".opened");

        $.each(opened, function (idx, elem) {
            var $elem = $(elem);

            if ($elem.hasClass("filter-risk-groups")) {
                $("input", $elem).each(function (i, e) {
                    $(e).prop("checked", false);
                });
            } else if ($elem.hasClass("filter-categories")) {
                $("#categories", $elem).children().empty();
                $("#choose-categories .filter-cats input").each(function (
                    i,
                    e
                ) {
                    var $e = $(e);
                    if ($e.data("default") == "yes") $e.prop("checked", true);
                    else $e.prop("checked", false);
                });
            } else if ($elem.hasClass("filter-cross-reference")) {
                $("#constraints", $elem).children().empty();
                $("#new-cross-reference #cr-categories option").each(function (
                    i,
                    e
                ) {
                    var $e = $(e);
                    $e.prop("disabled", false);
                });
            } else {
                if ($elem.find("select").length > 0) {
                    if ($elem.attr("id") == "profiles-option") {
                        $("#got-help", $elem).val("all");
                    } else {
                        $("select", $elem).each(function (i, e) {
                            $(e).val("0");
                        });
                    }
                } else {
                    $("input", $elem).each(function (i, e) {
                        $(e).val("");
                    });
                }
            }

            $elem.removeClass("opened");
        });

        //updateFilter();

        return;
    });

    $("#choose-compare-categories .save-categories").click(function (e) {
        e.preventDefault();
        $.magnificPopup.close();
    });

    $(".start-compare").click(function () {
        var req = { data: [] };
        var compareType = $("#compare-type").val();

        if (compareType == "") return;

        // Klasser
        if (compareType == "sections") {
            $("#compare-sections :selected").each(function (i, elem) {
                var $this = $(this);

                var label = $this
                    .html()
                    .trim()
                    .replace(/\s{2,}/g, " ");
                var value = $this.val();

                req.data.push({
                    type: "section",
                    name: label,
                    id: value,
                });
            });
        }

        // Program
        if (compareType == "programmes") {
            $("#compare-programmes :selected").each(function (i, elem) {
                var $this = $(this);

                var label = $this
                    .html()
                    .trim()
                    .replace(/\s{2,}/g, " ");
                var value = $this.val();

                req.data.push({
                    type: "programme",
                    name: label,
                    id: value,
                });
            });
        }

        if (compareType == "groups") {
            $("#compare-groups :selected").each(function (i, elem) {
                var $this = $(this);

                var label = $this
                    .html()
                    .trim()
                    .replace(/\s{2,}/g, " ");
                var value = $this.val();

                req.data.push({
                    type: "sample-group",
                    name: label,
                    id: value,
                });
            });
        }

        if (compareType == "user-filters") {
            $("#compare-user-filters :selected").each(function () {
                var $this = $(this);

                var label = $this
                    .html()
                    .trim()
                    .replace(/\s{2,}/g, " ");
                var filterOptions = $this.data("filter");
                var filters = {};
                var len = filterOptions.length;
                for (var i = 0; i < len; i++) {
                    var option = filterOptions[i];
                    filters[option.name] = option.value;
                }

                req.data.push({
                    type: "user-filter",
                    name: label,
                    filters: filters,
                });
            });
        }

        if (req.data.length == 0) {
            // felmeddelande
            return;
        }

        var elements = $(".opened .elements .categories");
        var categories = [];
        elements.children().each(function (eIdx, element) {
            var $elem = $(element),
                value = $elem.data("value");

            categories.push(value);
        });

        if (categories.length > 0) {
            req.categories = categories.join("|");
        }

        // console.log(req);

        $.ajax(fms_url + "/statistics/compare", {
            data: req,
            dataType: "json",
            method: "post",
            success: function (response) {
                $(".spinner").fadeOut(250);
                //console.log("fil:");
                //console.log(response);

                if (response.status != "ok") {
                    // console.log(response);
                    alert("Ett fel uppstod:\n" + response.message); // TODO: gör ordentlig
                    return;
                }

                var units = [];

                var labelsFixed = false;
                for (var i = 0; i < response.data.length; i++) {
                    var result = response.data[i];
                    if (result.barValuesStacked === undefined) continue;

                    if (!labelsFixed) {
                        setCompareLabels(result.barLabelsExt);
                        labelsFixed = true;
                    }

                    units.push({
                        name: result.label,
                        sexValues: [result.numWomen, result.numMen],
                        factorValues: result.barValuesStackedExt,
                    });

                    //console.log("unitsss: " + units);
                }

                if (units.length === 0) {
                    $(".inner .active").fadeOut(FADE_SPEED, function () {
                        $(".inner .active").removeClass("active");

                        $(".compare-empty").fadeIn();
                        $(".compare-empty").addClass("active");
                    });

                    return;
                }

                $("#compare-factors-container").empty();

                setCompareData(units);
                //console.log(units);
            },
            beforeSend: function () {
                $(".spinner").fadeIn(250);
                return true;
            },
        });
    });

    $(document).ready(function () {
        $("#options-link").parent().addClass("active-tab");
        $("#options-contents").addClass("active");

        window.compareSumoOpts = {
            placeholder: "Välj",
            captionFormat: "{0} valda",
        };

        $("#compare-sections").SumoSelect(compareSumoOpts);
        $("#compare-programmes").SumoSelect(compareSumoOpts);
        $("#compare-groups").SumoSelect(compareSumoOpts);
        $("#compare-user-filters").SumoSelect(compareSumoOpts);

        $(".compare-options a").click(function (ev) {
            var $e = $(ev.target);
            var target = $e.data("target");

            $(".compare-options").fadeOut(300, function () {
                $("#compare-type").val(target);
                $("#compare-" + target + "-container")
                    .fadeIn(300)
                    .addClass("selected");
                $(".compare-actions").fadeIn(300);
            });
        });

        $(".choose-new-option").click(function (ev) {
            var $e = $(ev.target);

            $(".compare-actions").fadeOut(300);
            $(".compare-option.selected").fadeOut(300);

            setTimeout(function () {
                $("#compare-type").val("");
                $(".compare-options").fadeIn();
                var selectBox = $(".compare-option.selected select");
                $("option:selected", selectBox).each(function (i) {
                    selectBox[0].sumo.unSelectItem(i);
                });
                $(".compare-option.selected").removeClass("selected");
            }, 300);
        });

        $(".new-cross-reference").magnificPopup({
            type: "inline",

            fixedContentPos: false,
            fixedBgPos: false,

            overflowY: "auto",

            closeBtnInside: true,
            preloader: false,

            midClick: true,
            removalDelay: 300,
            mainClass: "my-mfp-zoom-in",

            callbacks: {
                close: function () {
                    if ($("#saving").val() === "0") return;

                    $("#saving").val(0);

                    var $select = $(".filter-cr select"),
                        $checkboxes = $(".cr-checkboxes input"),
                        $target = $(
                            ".filter-cross-reference .elements .constraints"
                        );

                    var obj = {};

                    var ch = $select.find(":selected"); // inte .val() eftersom vi vill ha option elementet.
                    obj.factor = ch.val();
                    obj.label = ch.html();
                    obj.checked = [];

                    //disabledConstraints.push(ch);
                    //ch.prop("disabled", true); // avaktiverad för att inte vara inaktiv

                    var li = $(document.createElement("li"));
                    li.data("name", obj.factor);
                    //li.data("id", obj.factor)
                    li.html(obj.label);

                    var testar = $("#constraints li");

                    testar.each(function (idx, el) {
                        //alert(el);
                    });

                    $checkboxes.each(function (idx, el) {
                        var $el = $(el);
                        if (!$el.is(":checked")) return;

                        var span = $(document.createElement("span"));
                        span.addClass($el.next().attr("class"));
                        span.html($el.next().html());

                        li.append(span);

                        obj.checked.push($el.attr("id").replace("cr-", ""));
                    });

                    var rb = removeButton.clone();
                    rb.data("label", obj.label);
                    rb.click(removeCR);
                    li.append(rb);

                    $(".filter-cr .error").html("");
                    obj.checked = obj.checked.join(",");

                    li.data("checked", obj.checked);
                    $target.append(li);

                    //updateFilter();
                },
                afterClose: function () {
                    var $checkboxes = $(".cr-checkboxes input");

                    for (var i = 0; i < $checkboxes.length; i++) {
                        var chk = $($checkboxes[i]);
                        if (chk.is(":checked")) chk.prop("checked", false);
                    }

                    var $select = $(".cr-field select");
                    $select.val(0);
                },
            },
        });

        $(".compare-option .open-choose-categories").magnificPopup({
            type: "inline",

            fixedContentPos: false,
            fixedBgPos: false,

            overflowY: "auto",

            closeBtnInside: true,
            preloader: false,

            midClick: true,
            removalDelay: 300,
            mainClass: "my-mfp-zoom-in",

            callbacks: {
                close: function () {
                    //STUB("choose-compare-categories callback: close");
                    //return;

                    var $elements = $(
                            "#choose-compare-categories .filter-cats input"
                        ),
                        $target = $(".compare-categories #categories");

                    $target.empty();
                    $elements.each(function (idx, el) {
                        var $el = $(el);
                        if (!$el.prop("checked")) return;
                        var li = $(document.createElement("li"));
                        li.data("value", $el.attr("id"));
                        li.html($el.data("label"));
                        $target.append(li);
                    });
                },
            },
        });

        $(".export-filter").click(function () {
            var filterOptions = serializeFilter();

            $.ajax(fms_url + "/statistics/export", {
                data: filterOptions,
                method: "post",
                success: function (response) {
                    // console.log(response);
                    window.open(
                        fms_url + "/statistics/download/" + response.id,
                        "_blank"
                    );
                },
            });
        });
    }); // $(document).ready
})();
