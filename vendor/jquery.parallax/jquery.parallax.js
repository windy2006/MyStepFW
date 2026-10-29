/**
 * 框架调用：parallax(options|"destroy")
 * 功能说明：返回原选择集；按 mode/rate/effect/repeat/offset 配置节动画，destroy 恢复样式和事件。
 * 使用说明：完整参数和示例见 docs/frontend/vendor/README.md；保留原组件版权声明。
 */
/**
 * jquery.parallax.js - build a parallax page with awesome effect
 *
 * Written by
 * ----------
 * Windy2000 (windy2006@gmail.com)
 *
 * Licensed under the Apache License Version 2.0
 *
 * Release at https://github.com/windy2006/jquery.parallax
 *
 * Dependencies
 * ------------
 * jQuery (http://jquery.com)
 *
 **/
(function ($) {
  "use strict";
  let serial=$.data(document,'jqueryparallaxSerial')||0;
  const effects = [
    "slide-up",
    "slide-down",
    "slide-right",
    "slide-left",
    "flip-x",
    "flip-y",
    "zoom",
  ];
  $.fn.parallax = function (options) {
    return this.each(function () {
      if(typeof options==='string'){const instance=$(this).data('parallax');if(options!=='destroy')throw new Error('Unknown parallax method: '+options);if(instance)instance.destroy();return;}
      const root = this,
        $root = $(root),
        previous = $root.data("parallax");
      if (previous) previous.destroy();
      const opt = $.extend(
          { rate: 0.3, mode: 0, effect: "slide-up", repeat: false, offset: 0 },
          options,
        ),
        ns = ".parallax" + ($.data(document,'jqueryparallaxSerial',++serial),serial);
      const fullScreen = Number(opt.mode) === 1;
      const sections = Array.from(root.children),
        saved = new Map(),
        reduced = matchMedia("(prefers-reduced-motion: reduce)");
      let frame = 0,disposed=false;
      const baseTop = new Map();
      function snapshot(el) {
        if (!saved.has(el))
          saved.set(el, {
            style: el.getAttribute("style"),
            class: el.getAttribute("class"),
            shown: el.getAttribute("data-shown"),
          });
      }
      snapshot(root);
      sections.forEach((section) => {
        snapshot(section);
        Array.from(section.children).forEach((child) => {
          snapshot(child);
          baseTop.set(child, parseFloat(getComputedStyle(child).top) || 0);
        });
      });
      function clearAnimation(el) {
        effects.forEach((effect) => el.classList.remove("animation_" + effect));
        el.classList.remove("animation");
        el.style.animationDelay = "";
        el.style.animationFillMode = "";
      }
      function reset(el) {
        clearAnimation(el);
        el.style.opacity = "0";
        el.removeAttribute("data-shown");
      }
      function effectFor(el, section) {
        let effect = el.dataset.effect || section.dataset.effect || opt.effect;
        return effect === "rand" || effect === "random"
          ? effects[Math.floor(Math.random() * effects.length)]
          : effects.includes(effect)
            ? effect
            : "slide-up";
      }
      function animate(el, section, index, sequential) {
        if (disposed||el.dataset.shown === "y") return;
        clearAnimation(el);
        el.style.opacity = "1";
        el.dataset.shown = "y";
        if (!reduced.matches) {
          // Reading layout starts a fresh animation after re-entry.
          void el.offsetWidth;
          let effect = effectFor(el, section);
          if (sequential)
            effect =
              index === 0
                ? "zoom"
                : innerWidth > 768
                  ? index % 2
                    ? "slide-up"
                    : "slide-down"
                  : index % 2
                    ? "slide-left"
                    : "slide-right";
          el.style.animationDelay = sequential ? index * 0.5 + "s" : "0s";
          el.style.animationFillMode = "both";
          el.classList.add("animation", "animation_" + effect);
        }
        $root.trigger("parallax:enter", [el]);
      }
      const groups = new Map();
      sections.forEach((section) => {
        section.classList.add("parallax-list");
        if (section.dataset.bg) {
          section.style.backgroundImage =
            "url(" + JSON.stringify(section.dataset.bg) + ")";
          section.classList.add("parallax");
        }
        const name = section.dataset.func;
        if (name && name !== "show_1") groups.set(section, name);
        Array.from(section.children).forEach((child) => {
          child.style.opacity = "0";
          child.removeAttribute("data-shown");
        });
      });
      function sectionVisibility(section, visible) {
        const children = Array.from(section.children),
          group = groups.get(section);
        if (visible && section.dataset.shown !== "y") {
          section.dataset.shown = "y";
          if (
            typeof window[group] === "function" &&
            group !== "show_2" &&
            !reduced.matches
          ) {
            children.forEach((child) => {
              clearAnimation(child);
              child.style.opacity = "1";
              child.dataset.shown = "y";
              void child.offsetWidth;
            });
            window[group]($(section), section.dataset.effect || opt.effect);
            $root.trigger("parallax:enter", [section]);
          } else
            children.forEach((child, index) =>
              animate(child, section, index, group === "show_2"),
            );
        } else if (!visible && !reduced.matches && opt.repeat && section.dataset.shown === "y") {
          section.removeAttribute("data-shown");
          children.forEach(reset);
        }
      }
      function layoutTop(el) {
        let top = 0;
        for (let node = el; node; node = node.offsetParent) {
          top += node.offsetTop;
          if (node === el && getComputedStyle(node).position === "relative")
            top -=
              (parseFloat(getComputedStyle(node).top) || 0) -
              (baseTop.get(node) || 0);
        }
        return top;
      }
      // Visibility uses layout boxes, never the transient transformed animation box.
      function resize() {
        if (Number(opt.mode) === 1) {
          const height = Math.max(240, innerHeight - Number(opt.offset || 0));
          Object.assign(root.style, {
            height: height + "px",
            overflowY: "auto",
            overflowX: "hidden",
            scrollSnapType: "y mandatory",
            overscrollBehaviorY: "contain",
          });
          sections.forEach((section) => {
            section.style.minHeight = height + "px";
            section.style.scrollSnapAlign = "start";
          });
        }
        schedule();
      }
      function update() {
        frame = 0;
        const bounds = fullScreen ? root.getBoundingClientRect() : null,
          top = bounds ? bounds.top : 0;
        const visibleTop = fullScreen
            ? Math.max(0, bounds.top + root.clientTop)
            : 0,
          visibleBottom = fullScreen
            ? Math.min(
                innerHeight,
                bounds.top + root.clientTop + root.clientHeight,
              )
            : innerHeight;
        // Read section geometry once before animation/style writes.
        const rects = new Map(
          sections.map((section) => [section, section.getBoundingClientRect()]),
        );
        sections.forEach((section) => {
          const rect = rects.get(section),
            visible =
              visibleBottom > visibleTop &&
              rect.bottom > visibleTop &&
              rect.top < visibleBottom;
          if (fullScreen || groups.has(section))
            sectionVisibility(section, visible);
          else {
            const origin = layoutTop(section);
            Array.from(section.children).forEach((child) => {
              const childTop = rect.top + layoutTop(child) - origin,
                childBottom = childTop + child.offsetHeight;
              if (
                visible &&
                childBottom > visibleTop &&
                childTop < visibleBottom
              )
                animate(child, section, 0, false);
              else if (opt.repeat && !reduced.matches) reset(child);
            });
          }
        });
        sections.forEach((section) => {
          if (!section.dataset.bg) return;
          const rate = Number(section.dataset.rate ?? opt.rate);
          section.style.backgroundPosition =
            reduced.matches || innerWidth < 768
              ? "bottom center"
              : "center " +
                (rects.get(section).top - top) *
                  (Number.isFinite(rate) ? rate : 0.3) +
                "px";
        });
      }
      function schedule() {
        if (!disposed&&!frame) frame = requestAnimationFrame(update);
      }
      function motion() {
        if (reduced.matches)
          sections.forEach((section) =>
            Array.from(section.children).forEach((child) => {
              clearAnimation(child);
              child.style.opacity = "1";
            }),
          );
        schedule();
      }
      $(window)
        .on("resize" + ns, resize)
        .on("scroll" + ns, schedule);
      $root.on("scroll" + ns + " scrollend" + ns, schedule);
      reduced.addEventListener("change", motion);
      $root
        .attr("data-parallax-mode", String(opt.mode))
        .attr("data-parallax-repeat", String(!!opt.repeat));
      resize();
      motion();
      $root.data("parallax", {
        options: opt,
        destroy() {
          disposed=true;cancelAnimationFrame(frame);
          $(window).off(ns);
          $root.off(ns);
          reduced.removeEventListener("change", motion);
          saved.forEach((value, el) =>
            [
              ["style", value.style],
              ["class", value.class],
              ["data-shown", value.shown],
            ].forEach(([key, v]) =>
              v === null ? el.removeAttribute(key) : el.setAttribute(key, v),
            ),
          );
          $root
            .removeData("parallax")
            .removeAttr("data-parallax-mode data-parallax-repeat");
        },
      });
    });
  };
})(jQuery);
