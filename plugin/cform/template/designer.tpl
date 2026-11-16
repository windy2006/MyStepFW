<section id="cf-designer" data-cf-recovery="<!--recovery|html-->">
    <header class="cf-page-heading">
        <div>
            <h2><!--designer_title|html--></h2>
            <p><!--designer_help|html--></p>
        </div>
        <div>
            <a href="<!--back_url|html-->">返回表单列表</a>
            <a class="cf-recovery-link" href="<!--recovery_url|html-->">修复功能扩展</a>
        </div>
    </header>
    <form
        id="cf-design-form"
        onsubmit="return typeof checkForm!=='function' || checkForm(this)"
        method="post"
        action="<!--action|html-->"
    >
        <!--csrf-->
        <input type="hidden" name="action" value="<!--save_action|html-->" />
        <input type="hidden" name="revision" value="<!--revision|html-->" />
        <section class="cf-section" aria-labelledby="cf-basic-title">
            <h3 id="cf-basic-title">基本信息</h3>
            <div class="cf-basic-grid">
                <label>
                    中文名称
                    <input
                        class="form-control"
                        name="name"
                        value="<!--name|html-->"
                        maxlength="100"
                        required
                    />
                </label>
                <label>
                    英文名称
                    <input
                        class="form-control"
                        name="name_en"
                        value="<!--name_en|html-->"
                        maxlength="100"
                    />
                </label>
                <label>
                    截止日期
                    <input
                        class="form-control"
                        type="date"
                        name="expire"
                        value="<!--expire|html-->"
                    />
                    <small>留空不限，选定日期当天仍可填写。</small>
                </label>
                <label>
                    备注
                    <input
                        class="form-control"
                        name="notes"
                        value="<!--notes|html-->"
                        maxlength="2000"
                    />
                </label>
            </div>
            <div class="cf-flags">
                <label>
                    <input type="checkbox" name="active" value="1" <!--active--> />
                    开放填写
                </label>
                <label>
                    <input type="checkbox" name="public_list" value="1" <!--public_list--> />
                    公开提交列表
                </label>
            </div>
            <small>公开列表只显示单独设为“公开”的字段；管理员字段和附件不会公开。</small>
        </section>
        <section class="cf-section" aria-labelledby="cf-fields-title">
            <header class="cf-section-heading">
                <div>
                    <h3 id="cf-fields-title">
                        表单项目
                        <span id="cf-field-count" class="cf-count" aria-live="polite"></span>
                    </h3>
                    <p>
                        拖动左侧手柄调整顺序，保存后前台按此顺序显示。手柄聚焦后也可用上下方向键调整。字段索引改名会作为新字段，原记录仍保留旧值。
                    </p>
                </div>
                <div class="cf-toolbar">
                    <button type="button" class="btn btn-primary" id="cf-add-field">
                        添加项目
                    </button>
                    <button type="button" class="btn btn-light" id="cf-import-fields">
                        导入／导出
                    </button>
                </div>
            </header>
            <div class="cf-table-wrap">
                <table class="table cf-field-table">
                    <thead>
                        <tr>
                            <th scope="col">顺序</th>
                            <th scope="col">索引</th>
                            <th scope="col">名称</th>
                            <th scope="col">类型</th>
                            <th scope="col">说明</th>
                            <th scope="col">操作</th>
                        </tr>
                    </thead>
                    <tbody id="cf-fields"></tbody>
                </table>
            </div>
            <textarea name="fields_json" id="cf-json" hidden><!--fields_json|html--></textarea>
            <p id="cf-design-error" role="alert"></p>
        </section>
        <!--template_editor-->
        <footer class="cf-save-bar">
            <button type="submit" class="btn btn-primary"><!--save_label|html--></button>
            <a href="<!--back_url|html-->">返回</a>
            <small>浮动层中的“确定”只更新当前列表，最后点击“保存表单”才会保存到站点。</small>
        </footer>
    </form>
    <dialog
        id="cf-field-dialog"
        class="cf-dialog"
        aria-labelledby="cf-field-dialog-title"
        aria-describedby="cf-field-dialog-help"
    >
        <form id="cf-field-editor" method="dialog" novalidate>
            <header class="cf-modal-header">
                <div>
                    <h3 id="cf-field-dialog-title">添加表单项目</h3>
                    <p id="cf-field-dialog-help">设置一个字段，确定后返回列表。</p>
                </div>
                <button type="button" class="cf-close" data-close-dialog aria-label="关闭项目编辑">
                    ×
                </button>
            </header>
            <div class="cf-modal-body">
                <div class="cf-editor-grid">
                    <label for="cf-field-key">
                        项目索引
                        <input
                            class="form-control"
                            id="cf-field-key"
                            name="field_key"
                            pattern="[a-z][a-z0-9_]{0,39}"
                            maxlength="40"
                            required
                        />
                        <small>1至40位，小写字母开头，只可含小写字母、数字和下划线。</small>
                    </label>
                    <label for="cf-field-type">
                        数据类型
                        <select class="form-control" id="cf-field-type" name="type">
                            <option value="text">单行文本</option>
                            <option value="textarea">多行文本</option>
                            <option value="radio">单选</option>
                            <option value="checkbox">多选</option>
                            <option value="select">下拉选单</option>
                            <option value="file">附件上传</option>
                        </select>
                    </label>
                    <label for="cf-field-title">
                        中文名称
                        <input
                            class="form-control"
                            id="cf-field-title"
                            name="title"
                            maxlength="1000"
                        />
                    </label>
                    <label for="cf-field-title-en">
                        英文名称
                        <input
                            class="form-control"
                            id="cf-field-title-en"
                            name="title_en"
                            maxlength="1000"
                        />
                    </label>
                    <label for="cf-field-comment">
                        中文说明
                        <input
                            class="form-control"
                            id="cf-field-comment"
                            name="comment"
                            maxlength="1000"
                        />
                    </label>
                    <label for="cf-field-comment-en">
                        英文说明
                        <input
                            class="form-control"
                            id="cf-field-comment-en"
                            name="comment_en"
                            maxlength="1000"
                        />
                    </label>
                </div>
                <section id="cf-choice-settings" class="cf-editor-part" hidden>
                    <h4>选项设置</h4>
                    <p>每行一个选项；英文可留空，填写时须与中文按顺序一一对应。</p>
                    <div class="cf-editor-grid">
                        <label for="cf-field-choices-cn">
                            中文选项
                            <textarea
                                class="form-control"
                                id="cf-field-choices-cn"
                                name="value_cn"
                                rows="4"
                            ></textarea>
                        </label>
                        <label for="cf-field-choices-en">
                            英文选项
                            <textarea
                                class="form-control"
                                id="cf-field-choices-en"
                                name="value_en"
                                rows="4"
                            ></textarea>
                        </label>
                    </div>
                </section>
                <section id="cf-input-settings" class="cf-editor-part">
                    <h4>输入规则</h4>
                    <div class="cf-editor-grid">
                        <label for="cf-field-format">
                            数据格式
                            <select class="form-control" id="cf-field-format" name="format">
                                <option value="">任意格式</option>
                                <option value="email">电子邮件</option>
                                <option value="url">网址</option>
                                <option value="digital">纯数字</option>
                                <option value="number">数值</option>
                                <option value="date">日期</option>
                                <option value="mobile">电话号码</option>
                            </select>
                        </label>
                        <label for="cf-field-length">
                            长度限制
                            <input
                                class="form-control"
                                id="cf-field-length"
                                type="number"
                                name="length"
                                min="1"
                                max="20000"
                                step="1"
                            />
                            <small>留空使用系统默认长度。</small>
                        </label>
                        <label for="cf-field-default">
                            默认值
                            <input
                                class="form-control"
                                id="cf-field-default"
                                name="default"
                                maxlength="1000"
                            />
                        </label>
                        <label for="cf-field-default-en">
                            英文默认值
                            <input
                                class="form-control"
                                id="cf-field-default-en"
                                name="default_en"
                                maxlength="1000"
                            />
                        </label>
                    </div>
                </section>
                <div class="cf-flags cf-editor-part">
                    <label>
                        <input type="checkbox" name="required" />
                        必填项
                    </label>
                    <label>
                        <input type="checkbox" name="search" />
                        用于检索
                    </label>
                    <label>
                        <input type="checkbox" name="manager" />
                        仅管理员编辑
                    </label>
                    <label>
                        <input type="checkbox" name="public" />
                        在公开列表展示
                    </label>
                </div>
                <p id="cf-field-error" class="cf-error" role="alert"></p>
            </div>
            <footer class="cf-modal-footer">
                <button type="button" class="btn btn-light" data-close-dialog>取消</button>
                <button type="submit" class="btn btn-primary" id="cf-confirm-field">确定</button>
            </footer>
        </form>
    </dialog>
    <dialog id="cf-import-dialog" class="cf-dialog" aria-labelledby="cf-import-dialog-title">
        <form id="cf-import-editor" method="dialog">
            <header class="cf-modal-header">
                <div>
                    <h3 id="cf-import-dialog-title">导入／导出表单项目</h3>
                    <p>复制当前定义以导出，或粘贴 UTF-8 JSON 后应用。取消不会修改列表。</p>
                </div>
                <button type="button" class="cf-close" data-close-dialog aria-label="关闭导入导出">
                    ×
                </button>
            </header>
            <div class="cf-modal-body">
                <label for="cf-import-json">
                    字段定义
                    <textarea
                        class="form-control cf-json-editor"
                        id="cf-import-json"
                        rows="15"
                        spellcheck="false"
                    ></textarea>
                </label>
                <p id="cf-import-error" class="cf-error" role="alert"></p>
                <p id="cf-copy-status" role="status"></p>
            </div>
            <footer class="cf-modal-footer">
                <button type="button" class="btn btn-light" id="cf-copy-json">复制定义</button>
                <span class="cf-footer-spacer"></span>
                <button type="button" class="btn btn-light" data-close-dialog>取消</button>
                <button type="submit" class="btn btn-primary" id="cf-apply-json">
                    应用字段定义
                </button>
            </footer>
        </form>
    </dialog>
</section>
<style>
    <!--designer_style-->
</style>
<script>
    <!--designer_script-->
</script>
