/**
 * 字段列表＋独立浮动编辑层。草稿确定后才写回模型，取消不改变任何字段。
 * 字段JSON契约与服务端校验保持不变；不依赖jQuery或宿主弹窗版本。
 */
(function () {
    "use strict";
    const root = document.getElementById("cf-designer");
    if (!root || root.dataset.ready) return;
    const q = (selector) => root.querySelector(selector),
        area = q("#cf-json"),
        list = q("#cf-fields"),
        error = q("#cf-design-error"),
        mainForm = q("#cf-design-form");
    const fieldDialog = q("#cf-field-dialog"),
        fieldForm = q("#cf-field-editor"),
        fieldError = q("#cf-field-error"),
        importDialog = q("#cf-import-dialog"),
        importForm = q("#cf-import-editor"),
        importArea = q("#cf-import-json"),
        importError = q("#cf-import-error");
    if (
        !area ||
        !list ||
        !error ||
        !mainForm ||
        !fieldDialog ||
        !fieldForm ||
        !importDialog ||
        !importForm
    )
        return;
    const types = {
        text: "单行文本",
        textarea: "多行文本",
        radio: "单选",
        select: "下拉选单",
        checkbox: "多选",
        file: "附件上传"
    };
    const formats = new Set(["", ".", "email", "url", "digital", "number", "date", "mobile"]);
    const reserved = new Set([
        "id",
        "mid",
        "mailed",
        "add_date",
        "append",
        "print",
        "_ms_csrf",
        "action",
        "request_key",
        "csv",
        "revision",
        "fields_json"
    ]);
    const choiceTypes = new Set(["radio", "select", "checkbox"]),
        textKeys = ["title", "title_en", "comment", "comment_en", "default", "default_en"];
    const flag = (value) => [true, 1, "1", "true", "on"].includes(value),
        clone = (value) => JSON.parse(JSON.stringify(value));
    const required = (field) =>
        Object.hasOwn(field, "required")
            ? flag(field.required)
            : !!(field.format && field.format !== "." && !field.format.endsWith("_"));
    const baseFormat = (field) => (field.format || "").replace(/_+$/, "").replace(/^\.$/, "");
    const control = (name) => fieldForm.elements.namedItem(name);
    let fields,
        draft = null,
        originalKey = null,
        activeDialog = null,
        returnFocus = null,
        fallbackBackdrop = null,
        oldOverflow = "",
        drag = null;
    function validKey(key) {
        return /^[a-z][a-z0-9_]{0,39}$/.test(key) && !reserved.has(key);
    }
    /** 在替换模型前验证所有字段；错误导入不得损坏已有设计。 */
    function validate(value) {
        if (!value || Array.isArray(value) || typeof value !== "object")
            throw Error("需要字段对象");
        const entries = Object.entries(value);
        if (!entries.length || entries.length > 100) throw Error("字段数应为1至100");
        for (const [key, field] of entries) {
            if (!validKey(key)) throw Error("字段索引无效或为保留名称：" + key);
            if (
                !field ||
                Array.isArray(field) ||
                typeof field !== "object" ||
                !Object.hasOwn(types, field.type || "text")
            )
                throw Error("字段类型无效：" + key);
            for (const name of [...textKeys, "format"])
                if (
                    field[name] !== undefined &&
                    (typeof field[name] !== "string" ||
                        Array.from(field[name]).length > (name === "format" ? 100 : 1000))
                )
                    throw Error("字段文字格式或长度无效：" + key + "." + name);
            if (!field.title && !field.title_en) throw Error("请填写中文名称或英文名称：" + key);
            if (
                field.length !== undefined &&
                field.length !== "" &&
                (!/^[0-9]+$/.test(String(field.length)) ||
                    Number(field.length) < 1 ||
                    Number(field.length) > 20000)
            )
                throw Error("字段长度应为1至20000：" + key);
            if (!formats.has((field.format || "").replace(/_+$/, "")))
                throw Error("字段格式不受支持：" + key);
            if (choiceTypes.has(field.type)) {
                const choices = field.value;
                if (!choices || Array.isArray(choices) || typeof choices !== "object")
                    throw Error("选项应包含cn/en数组：" + key);
                for (const lng of ["cn", "en"]) {
                    const options = choices[lng] ?? [];
                    if (
                        !Array.isArray(options) ||
                        options.length > 100 ||
                        options.some(
                            (v) => typeof v !== "string" || !v.trim() || Array.from(v).length > 255
                        ) ||
                        new Set(options).size !== options.length
                    )
                        throw Error("选项应为非空且不重复的文字：" + key);
                }
                if (
                    !choices.cn?.length ||
                    (choices.en?.length && choices.en.length !== choices.cn.length)
                )
                    throw Error("中英文选项须按顺序一一对应：" + key);
                for (const lng of ["cn", "en"]) {
                    const def = field[lng === "cn" ? "default" : "default_en"] || "";
                    if (!def) continue;
                    const options = lng === "en" && choices.en?.length ? choices.en : choices.cn;
                    const defaults =
                        field.type === "checkbox" && !options.includes(def)
                            ? def.split(",").map((v) => v.trim())
                            : [def];
                    if (defaults.some((v) => !options.includes(v)))
                        throw Error("默认值不在已定义选项中：" + key);
                }
            }
        }
        return value;
    }
    try {
        fields = validate(JSON.parse(area.value));
    } catch (e) {
        error.textContent = "字段初始化失败：" + e.message;
        return;
    }
    root.dataset.ready = "1";
    function sync() {
        area.value = JSON.stringify(fields, null, 2);
    }
    function button(action, label, key, disabled = false) {
        const el = document.createElement("button");
        el.type = "button";
        el.dataset.action = action;
        el.textContent = label;
        el.disabled = disabled;
        el.setAttribute("aria-label", label + " " + key);
        return el;
    }
    /** 列表只展示摘要，字段值始终用textContent写入，避免解释为HTML。 */
    function draw() {
        const entries = Object.entries(fields),
            fragment = document.createDocumentFragment();
        entries.forEach(([key, field], index) => {
            const row = document.createElement("tr");
            row.dataset.key = key;
            const cell = (text) => {
                const td = document.createElement("td");
                td.textContent = text;
                row.append(td);
                return td;
            };
            const order = cell(""),
                handle = button("drag", "⠿", key);
            handle.className = "cf-drag-handle";
            handle.setAttribute("aria-label", "拖动排序 " + key);
            handle.title = "拖动排序，或用上下方向键调整";
            order.append(handle, document.createTextNode(String(index + 1)));
            cell(key);
            const name = cell(""),
                title = document.createElement("span");
            title.className = "cf-field-name";
            title.textContent = field.title || field.title_en;
            name.append(title);
            const badges = document.createElement("span");
            badges.className = "cf-badges";
            for (const [yes, label] of [
                [required(field), "必填"],
                [flag(field.manager), "管理员"],
                [flag(field.public), "公开"],
                [flag(field.search), "检索"]
            ])
                if (yes) {
                    const badge = document.createElement("span");
                    badge.className = "cf-badge";
                    badge.textContent = label;
                    badges.append(badge);
                }
            name.append(badges);
            cell(types[field.type || "text"]);
            cell(field.comment || field.comment_en || "");
            const actions = cell(""),
                group = document.createElement("div");
            group.className = "cf-row-actions";
            group.append(button("edit", "编辑", key), button("delete", "删除", key));
            actions.append(group);
            fragment.append(row);
        });
        list.replaceChildren(fragment);
        q("#cf-field-count").textContent = "共 " + entries.length + " 项";
    }
    function show(dialog, opener) {
        if (activeDialog) return;
        activeDialog = dialog;
        returnFocus = opener || document.activeElement;
        oldOverflow = document.body.style.overflow;
        document.body.style.overflow = "hidden";
        if (typeof dialog.showModal === "function") dialog.showModal();
        else {
            fallbackBackdrop = document.createElement("div");
            fallbackBackdrop.className = "cf-modal-backdrop";
            root.append(fallbackBackdrop);
            dialog.setAttribute("open", "");
            dialog.setAttribute("role", "dialog");
            dialog.setAttribute("aria-modal", "true");
            mainForm.inert = true;
        }
        dialog.querySelector(".cf-modal-body").scrollTop = 0;
        const target = dialog === fieldDialog ? control("field_key") : importArea;
        target.focus();
    }
    function close(focusTarget) {
        if (!activeDialog) return;
        const dialog = activeDialog;
        activeDialog = null;
        if (typeof dialog.close === "function") dialog.close();
        else dialog.removeAttribute("open");
        fallbackBackdrop?.remove();
        fallbackBackdrop = null;
        mainForm.inert = false;
        document.body.style.overflow = oldOverflow;
        draft = null;
        originalKey = null;
        const target = focusTarget || returnFocus;
        if (target?.isConnected) target.focus();
        else q("#cf-add-field").focus();
        returnFocus = null;
    }
    function updateType() {
        const type = control("type").value;
        q("#cf-choice-settings").hidden = !choiceTypes.has(type);
        q("#cf-input-settings").hidden = type === "file";
        for (const el of q("#cf-input-settings").querySelectorAll("input,select"))
            el.disabled = type === "file";
    }
    function openField(key, opener) {
        if (key === null && Object.keys(fields).length >= 100) {
            error.textContent = "最多100个字段";
            return;
        }
        originalKey = key;
        draft =
            key === null
                ? {
                      type: "text",
                      title: "",
                      title_en: "",
                      comment: "",
                      comment_en: "",
                      default: "",
                      default_en: "",
                      format: "",
                      length: 255,
                      required: false,
                      search: false,
                      manager: false,
                      public: false,
                      value: { cn: [], en: [] }
                  }
                : clone(fields[key]);
        fieldForm.reset();
        let next = 1;
        while (Object.hasOwn(fields, "field_" + next)) next++;
        control("field_key").value = key ?? "field_" + next;
        control("type").value = draft.type || "text";
        for (const name of textKeys) control(name).value = draft[name] ?? "";
        control("format").value = baseFormat(draft);
        control("length").value = draft.length ?? "";
        for (const name of ["search", "manager", "public"])
            control(name).checked = flag(draft[name]);
        control("required").checked = required(draft);
        for (const lng of ["cn", "en"])
            control("value_" + lng).value = Array.isArray(draft.value?.[lng])
                ? draft.value[lng].join("\n")
                : "";
        q("#cf-field-dialog-title").textContent = key === null ? "添加表单项目" : "编辑表单项目";
        fieldError.textContent = "";
        updateType();
        show(fieldDialog, opener);
    }
    function confirmField(event) {
        event.preventDefault();
        event.stopPropagation();
        if (!draft) return;
        try {
            const key = control("field_key").value;
            if (!/^[a-z][a-z0-9_]{0,39}$/.test(key)) {
                control("field_key").focus();
                throw Error("项目索引须为1至40位，以小写字母开头，只可含小写字母、数字和下划线");
            }
            if (reserved.has(key)) throw Error("项目索引为系统保留名称，请更换");
            if (key !== originalKey && Object.hasOwn(fields, key))
                throw Error("项目索引已存在，请使用其他索引");
            const value = clone(draft);
            value.type = control("type").value;
            const isFile = value.type === "file";
            for (const name of textKeys)
                if (!isFile || !["default", "default_en"].includes(name))
                    value[name] = control(name).value;
            value.length = isFile ? (draft.length ?? "") : control("length").value;
            const selected = control("format").value;
            value.format = isFile || selected === baseFormat(draft) ? draft.format || "" : selected;
            for (const name of ["required", "search", "manager", "public"])
                value[name] = control(name).checked;
            if (choiceTypes.has(value.type)) {
                value.value = { cn: [], en: [] };
                for (const lng of ["cn", "en"]) {
                    const lines = control("value_" + lng)
                        .value.replace(/\r/g, "")
                        .trim();
                    value.value[lng] = lines === "" ? [] : lines.split("\n").map((v) => v.trim());
                }
            }
            const next =
                originalKey === null
                    ? { ...fields, [key]: value }
                    : Object.fromEntries(
                          Object.entries(fields).map(([k, v]) =>
                              k === originalKey ? [key, value] : [k, v]
                          )
                      );
            validate(next);
            fields = next;
            sync();
            draw();
            error.textContent = "";
            close(list.querySelector('tr[data-key="' + key + '"] [data-action="edit"]'));
        } catch (e) {
            fieldError.textContent = e.message;
            fieldError.scrollIntoView?.({ block: "nearest" });
        }
    }
    fieldForm.addEventListener("submit", confirmField);
    // 点击直接进入同一校验流程，避免宿主或浏览器先行拦截dialog表单的提交。
    q("#cf-confirm-field").addEventListener("click", confirmField);
    control("type").addEventListener("change", updateType);
    q("#cf-add-field").addEventListener("click", (event) => openField(null, event.currentTarget));
    list.addEventListener("click", (event) => {
        const action = event.target.closest("button[data-action]"),
            row = action?.closest("tr[data-key]");
        if (!row || action.disabled) return;
        const key = row.dataset.key;
        if (action.dataset.action === "edit") {
            openField(key, action);
            return;
        }
        if (action.dataset.action === "delete") {
            if (Object.keys(fields).length === 1) {
                error.textContent = "至少需要保留一个字段";
                return;
            }
            if (
                !window.confirm(
                    "确认移除项目“" +
                        (fields[key].title || fields[key].title_en) +
                        "”？已有记录中的值会保留。"
                )
            )
                return;
            delete fields[key];
            sync();
            draw();
            error.textContent = "";
            return;
        }
    });
    /** DOM只用于拖动预览；释放后一次性按列表顺序写回字段对象。 */
    function commitOrder(key) {
        fields = Object.fromEntries(
            Array.from(list.children, (row) => [row.dataset.key, fields[row.dataset.key]])
        );
        sync();
        draw();
        list.querySelector('tr[data-key="' + key + '"] .cf-drag-handle')?.focus();
        error.textContent = "";
    }
    function finishDrag(commit) {
        if (!drag) return;
        const key = drag.key,
            handle = drag.handle,
            id = drag.id;
        drag = null;
        try {
            if (handle.hasPointerCapture?.(id)) handle.releasePointerCapture(id);
        } catch (e) {}
        document.body.classList.remove("cf-sorting");
        if (commit) commitOrder(key);
        else draw();
    }
    list.addEventListener("pointerdown", (event) => {
        const handle = event.target.closest(".cf-drag-handle");
        if (!handle || event.button !== 0 || activeDialog) return;
        const row = handle.closest("tr");
        drag = { key: row.dataset.key, row, handle, id: event.pointerId };
        row.classList.add("cf-dragging");
        document.body.classList.add("cf-sorting");
        event.preventDefault();
    });
    document.addEventListener(
        "pointermove",
        (event) => {
            if (!drag || event.pointerId !== drag.id) return;
            event.preventDefault();
            const target = document
                .elementFromPoint(event.clientX, event.clientY)
                ?.closest("#cf-fields tr");
            if (target && target !== drag.row) {
                const box = target.getBoundingClientRect();
                list.insertBefore(
                    drag.row,
                    event.clientY < box.top + box.height / 2 ? target : target.nextSibling
                );
            }
            if (event.clientY < 70) window.scrollBy(0, -24);
            else if (event.clientY > window.innerHeight - 70) window.scrollBy(0, 24);
        },
        { passive: false }
    );
    document.addEventListener("pointerup", (event) => {
        if (drag && event.pointerId === drag.id) finishDrag(true);
    });
    document.addEventListener("pointercancel", () => finishDrag(false));
    window.addEventListener("blur", () => finishDrag(false));
    list.addEventListener("keydown", (event) => {
        const handle = event.target.closest(".cf-drag-handle");
        if (!handle || !["ArrowUp", "ArrowDown", "Home", "End"].includes(event.key)) return;
        event.preventDefault();
        const row = handle.closest("tr"),
            key = row.dataset.key;
        if (event.key === "Home") list.prepend(row);
        else if (event.key === "End") list.append(row);
        else if (event.key === "ArrowUp" && row.previousElementSibling)
            list.insertBefore(row, row.previousElementSibling);
        else if (event.key === "ArrowDown" && row.nextElementSibling)
            list.insertBefore(row, row.nextElementSibling.nextSibling);
        commitOrder(key);
    });
    q("#cf-import-fields").addEventListener("click", (event) => {
        importArea.value = JSON.stringify(fields, null, 2);
        importError.textContent = "";
        q("#cf-copy-status").textContent = "";
        show(importDialog, event.currentTarget);
        importArea.setSelectionRange(0, 0);
        importArea.scrollTop = 0;
    });
    importForm.addEventListener("submit", (event) => {
        event.preventDefault();
        try {
            const next = validate(JSON.parse(importArea.value));
            fields = next;
            sync();
            draw();
            error.textContent = "";
            close(q("#cf-import-fields"));
        } catch (e) {
            importError.textContent = "JSON无效：" + e.message;
        }
    });
    q("#cf-copy-json").addEventListener("click", async () => {
        try {
            await navigator.clipboard.writeText(importArea.value);
            q("#cf-copy-status").textContent = "已复制当前定义";
        } catch (e) {
            importArea.focus();
            importArea.select();
            q("#cf-copy-status").textContent = "请按 Ctrl+C 或 ⌘C 复制所选定义";
        }
    });
    for (const dialog of [fieldDialog, importDialog]) {
        for (const btn of dialog.querySelectorAll("[data-close-dialog]"))
            btn.addEventListener("click", () => close());
        dialog.addEventListener("cancel", (event) => {
            event.preventDefault();
            close();
        });
    }
    document.addEventListener("keydown", (event) => {
        if (drag && event.key === "Escape") {
            event.preventDefault();
            finishDrag(false);
            return;
        }
        if (!activeDialog) return;
        if (event.key === "Escape") {
            event.preventDefault();
            close();
            return;
        }
        if (event.key === "Tab" && fallbackBackdrop) {
            const inputs = Array.from(
                activeDialog.querySelectorAll("button,input,select,textarea,a[href]")
            ).filter((el) => !el.disabled && !el.closest("[hidden]"));
            const first = inputs[0],
                last = inputs[inputs.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
    mainForm.addEventListener("submit", (event) => {
        if (activeDialog) {
            event.preventDefault();
            error.textContent = "请先确定或取消浮动层中的编辑";
            return;
        }
        try {
            validate(JSON.parse(area.value));
        } catch (e) {
            event.preventDefault();
            error.textContent = "字段定义无效：" + e.message;
        }
    });
    const selector = q("#cf-template-select"),
        settings = q("#cf-template-settings"),
        editors = new Map();
    if (selector && settings) {
        const panels = Array.from(root.querySelectorAll("[data-cf-template-panel]")),
            status = q("#cf-editor-status");
        const normalize = (value) => value.replace(/\r\n?/g, "\n");
        const defaults = (panel) => panel.querySelector("[data-cf-default-source]").textContent;
        const inherits = (panel, value) =>
            value.trim() === "" || normalize(value) === normalize(defaults(panel));
        const refreshState = (panel) => {
            const input = panel.querySelector("textarea");
            panel.querySelector("[data-cf-template-state]").textContent = inherits(
                panel,
                input.value
            )
                ? "继承默认"
                : "单表单覆盖";
        };
        let loading = null;
        function loadEditor() {
            if (loading) return loading;
            const base = settings.dataset.cfEditorBase;
            if (!base) return Promise.reject(Error("缺少编辑器资源地址"));
            if (!document.querySelector("link[data-cf-editor-css]")) {
                const link = document.createElement("link");
                link.rel = "stylesheet";
                link.href = base + "lib/codemirror.css";
                link.dataset.cfEditorCss = "1";
                document.head.append(link);
            }
            const files = [
                ["lib/codemirror.js", () => window.CodeMirror],
                ["mode/xml/xml.js", () => window.CodeMirror?.modes.xml],
                ["mode/javascript/javascript.js", () => window.CodeMirror?.modes.javascript],
                ["mode/css/css.js", () => window.CodeMirror?.modes.css],
                ["mode/htmlmixed/htmlmixed.js", () => window.CodeMirror?.modes.htmlmixed],
                ["mode/clike/clike.js", () => window.CodeMirror?.modes.clike],
                ["mode/php/php.js", () => window.CodeMirror?.modes.php],
                [
                    "addon/edit/matchbrackets.js",
                    () => window.CodeMirror?.defaults.matchBrackets !== undefined
                ],
                [
                    "addon/selection/active-line.js",
                    () => window.CodeMirror?.defaults.styleActiveLine !== undefined
                ]
            ];
            loading = files.reduce(
                (promise, [file, ready]) =>
                    promise.then(() => {
                        if (ready()) return;
                        return new Promise((resolve, reject) => {
                            const script = document.createElement("script");
                            script.src = base + file;
                            script.onload = () =>
                                ready() ? resolve() : reject(Error("编辑器组件未就绪：" + file));
                            script.onerror = () => {
                                script.remove();
                                reject(Error("无法加载 " + file));
                            };
                            document.head.append(script);
                        });
                    }),
                Promise.resolve()
            );
            return loading;
        }
        function activate() {
            if (!settings.open) return;
            loadEditor()
                .then(() => {
                    if (!settings.open) return;
                    const panel = panels.find((item) => !item.hidden);
                    if (!panel) return;
                    const input = panel.querySelector("textarea");
                    let editor = editors.get(input);
                    if (!editor) {
                        editor = window.CodeMirror.fromTextArea(input, {
                            mode:
                                panel.dataset.cfTemplatePanel === "extension"
                                    ? "application/x-httpd-php-open"
                                    : "application/x-httpd-php",
                            lineNumbers: true,
                            lineWrapping: true,
                            indentUnit: 4,
                            tabSize: 4,
                            matchBrackets: true,
                            styleActiveLine: true
                        });
                        editors.set(input, editor);
                        editor.setSize("100%", 360);
                        editor
                            .getInputField()
                            .setAttribute(
                                "aria-label",
                                input.getAttribute("aria-label") + "代码编辑器"
                            );
                        editor.on("change", () => {
                            editor.save();
                            refreshState(panel);
                        });
                    }
                    editor.refresh();
                    status.textContent = "代码高亮已启用 · 行号 · 括号匹配 · Tab缩进";
                })
                .catch(() => {
                    status.textContent = "代码高亮资源暂未加载，可继续使用源码文本框编辑和保存。";
                    loading = null;
                });
        }
        const setSource = (panel, value) => {
            const input = panel.querySelector("textarea"),
                editor = editors.get(input);
            if (editor) {
                editor.setValue(value);
                editor.save();
            } else input.value = value;
            refreshState(panel);
        };
        selector.addEventListener("change", () => {
            for (const panel of panels)
                panel.hidden = panel.dataset.cfTemplatePanel !== selector.value;
            activate();
        });
        settings.addEventListener("toggle", activate);
        // 在宿主onsubmit/checkForm之前同步，原生提交与FormData都使用最新源码。
        mainForm.addEventListener(
            "submit",
            () => {
                for (const editor of editors.values()) editor.save();
            },
            true
        );
        for (const panel of panels) {
            const input = panel.querySelector("textarea");
            input.addEventListener("input", () => refreshState(panel));
            panel.querySelector("[data-cf-copy-default]").addEventListener("click", () => {
                if (
                    !inherits(panel, input.value) &&
                    !window.confirm("覆盖此表单当前源码为默认内容？")
                )
                    return;
                setSource(panel, defaults(panel));
            });
            panel.querySelector("[data-cf-reset-template]").addEventListener("click", () => {
                if (
                    !inherits(panel, input.value) &&
                    !window.confirm("清除此表单的覆盖，恢复继承默认模板？")
                )
                    return;
                setSource(panel, defaults(panel));
            });
            refreshState(panel);
        }
        activate();
    }
    draw();
})();
