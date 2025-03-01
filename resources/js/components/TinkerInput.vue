<template>
    <section class="input"><textarea ref="codeEditor" /></section>
</template>

<script>
import 'codemirror/mode/php/php';
import 'codemirror/addon/hint/show-hint';
import 'codemirror/addon/hint/anyword-hint';
import 'codemirror/addon/hint/show-hint.css';
import 'codemirror/addon/edit/matchbrackets';
import 'codemirror/addon/edit/closebrackets';
import 'codemirror/addon/selection/active-line';
import 'codemirror/theme/dracula.css';
import 'codemirror/theme/monokai.css';
import 'codemirror/theme/material.css';
import 'codemirror/theme/nord.css';
import 'codemirror/theme/solarized.css';

import CodeMirror from 'codemirror';
import axios from 'axios';

export default {
    data: () => ({
        value: '',
        codeEditor: null,
        phpKeywords: [
            'User::all()', 'DB::table(', 'where(', 'select(', 'join(',
            'orderBy(', 'groupBy(', 'get()', 'first()', 'find(',
            'create(', 'update(', 'delete()', 'with(', 'has(',
            'whereHas(', 'whereIn(', 'whereBetween(', 'whereNull(',
            'whereNotNull(', 'count()', 'sum(', 'avg(', 'max(', 'min(',
            // Add more Laravel specific methods
            'Auth::', 'Cache::', 'Config::', 'Route::', 'Session::',
            'Storage::', 'Hash::', 'Validator::', 'Event::', 'Log::'
        ],
        // تعريف اقتراحات مخصصة حسب السياق
        contextualSuggestions: {
            // اقتراحات للنماذج (Models)
            model: [
                'all()', 'find()', 'findOrFail()', 'first()', 'firstOrFail()',
                'where()', 'whereIn()', 'whereNotIn()', 'whereBetween()', 'whereNotBetween()',
                'whereNull()', 'whereNotNull()', 'whereHas()', 'whereDoesntHave()',
                'with()', 'has()', 'doesntHave()', 'withCount()',
                'orderBy()', 'orderByDesc()', 'latest()', 'oldest()',
                'skip()', 'take()', 'offset()', 'limit()',
                'get()', 'paginate()', 'simplePaginate()', 'count()',
                'max()', 'min()', 'avg()', 'sum()',
                'create()', 'update()', 'delete()', 'destroy()',
                'save()', 'saveOrFail()', 'push()', 'touch()',
                'increment()', 'decrement()', 'replicate()', 'refresh()'
            ],
            // اقتراحات لـ DB
            db: [
                'table()', 'select()', 'selectRaw()', 'from()',
                'join()', 'leftJoin()', 'rightJoin()', 'crossJoin()',
                'where()', 'whereIn()', 'whereNotIn()', 'whereBetween()',
                'whereExists()', 'whereNotExists()', 'whereNull()', 'whereNotNull()',
                'orderBy()', 'orderByDesc()', 'groupBy()', 'having()',
                'skip()', 'take()', 'offset()', 'limit()',
                'get()', 'first()', 'value()', 'count()',
                'max()', 'min()', 'avg()', 'sum()',
                'insert()', 'update()', 'delete()', 'truncate()',
                'transaction()', 'beginTransaction()', 'commit()', 'rollBack()'
            ],
            // اقتراحات للمجموعات (Collections)
            collection: [
                'all()', 'avg()', 'chunk()', 'collapse()', 'collect()',
                'combine()', 'concat()', 'contains()', 'containsStrict()',
                'count()', 'countBy()', 'diff()', 'diffAssoc()', 'diffKeys()',
                'each()', 'every()', 'except()', 'filter()', 'first()',
                'firstWhere()', 'flatMap()', 'flatten()', 'flip()', 'forget()',
                'forPage()', 'get()', 'groupBy()', 'has()', 'implode()',
                'intersect()', 'isEmpty()', 'isNotEmpty()', 'keyBy()', 'keys()',
                'last()', 'map()', 'mapInto()', 'mapSpread()', 'mapToGroups()',
                'mapWithKeys()', 'max()', 'median()', 'merge()', 'min()',
                'mode()', 'nth()', 'only()', 'pad()', 'partition()',
                'pipe()', 'pluck()', 'pop()', 'prepend()', 'pull()',
                'push()', 'put()', 'random()', 'reduce()', 'reject()',
                'reverse()', 'search()', 'shift()', 'shuffle()', 'slice()',
                'sort()', 'sortBy()', 'sortByDesc()', 'splice()', 'split()',
                'sum()', 'take()', 'tap()', 'times()', 'toArray()',
                'toJson()', 'transform()', 'union()', 'unique()', 'uniqueStrict()',
                'unless()', 'unlessEmpty()', 'unlessNotEmpty()', 'unwrap()', 'values()',
                'when()', 'whenEmpty()', 'whenNotEmpty()', 'where()', 'whereStrict()',
                'whereBetween()', 'whereIn()', 'whereInStrict()', 'whereInstanceOf()', 'whereNotBetween()',
                'whereNotIn()', 'whereNotInStrict()', 'wrap()', 'zip()'
            ]
        },
        phpClasses: [],
        importedClasses: new Set(),
        lastImportLine: 0,
        isLoadingClasses: false,
        suggestions: [
            { text: 'map', displayText: 'map - تحويل عناصر المجموعة', snippet: 'map(function ($item) {\n    return $item;\n})' },
            { text: 'each', displayText: 'each - تنفيذ دالة على كل عنصر', snippet: 'each(function ($item) {\n    //\n})' },
            { text: 'filter', displayText: 'filter - تصفية العناصر', snippet: 'filter(function ($item) {\n    return true;\n})' },
            { text: 'reduce', displayText: 'reduce - تقليص المجموعة إلى قيمة واحدة', snippet: 'reduce(function ($carry, $item) {\n    return $carry + $item;\n})' },
            { text: 'reject', displayText: 'reject - استبعاد العناصر', snippet: 'reject(function ($item) {\n    return false;\n})' },
            { text: 'every', displayText: 'every - التحقق من جميع العناصر', snippet: 'every(function ($item) {\n    return true;\n})' },
            { text: 'some', displayText: 'some - التحقق من بعض العناصر', snippet: 'some(function ($item) {\n    return true;\n})' },
            { text: 'sort', displayText: 'sort - ترتيب العناصر', snippet: 'sort(function ($a, $b) {\n    return $a <=> $b;\n})' },
            { text: 'sortBy', displayText: 'sortBy - ترتيب حسب مفتاح', snippet: 'sortBy(\'id\')' },
            { text: 'sortByDesc', displayText: 'sortByDesc - ترتيب تنازلي حسب مفتاح', snippet: 'sortByDesc(\'id\')' },
            { text: 'groupBy', displayText: 'groupBy - تجميع حسب مفتاح', snippet: 'groupBy(\'id\')' },
            { text: 'partition', displayText: 'partition - تقسيم المجموعة', snippet: 'partition(function ($item) {\n    return $item > 10;\n})' },
        ]
    }),

    props: ['path'],

    mounted() {
        const config = {
            autofocus: true,
            extraKeys: {
                'Cmd-Enter': () => {
                    this.executeCode();
                },
                'Ctrl-Enter': () => {
                    this.executeCode();
                },
                'Ctrl-Space': (cm) => {
                    CodeMirror.showHint(cm, this.showHints.bind(this), {completeSingle: false});
                },
                'Tab': (cm) => {
                    if (cm.somethingSelected()) {
                        cm.indentSelection('add');
                    } else {
                        this.handleTabCompletion(cm);
                    }
                },
                'Alt-I': (cm) => {
                    this.importClass(cm);
                }
            },
            indentWithTabs: true,
            lineNumbers: true,
            lineWrapping: true,
            mode: {
                name: 'php',
                startOpen: true,
                htmlMode: false
            },
            tabSize: 4,
            theme: 'vscode-dark',
            matchBrackets: true,
            autoCloseBrackets: true,
            styleActiveLine: true,
            indentUnit: 4
        };

        this.codeEditor = CodeMirror.fromTextArea(this.$refs.codeEditor, config);

        this.codeEditor.on('change', editor => {
            localStorage.setItem('tinker-tool', editor.getValue());
            this.checkForClassImport(editor);
        });

        // إضافة مستمع للصق (paste) لاستيراد الفئات تلقائيًا
        this.codeEditor.on('paste', (editor, event) => {
            // استخدام setTimeout للسماح للصق بالاكتمال أولاً
            setTimeout(() => {
                this.handlePastedCode(editor);
            }, 100);
        });

        this.codeEditor.on('keyup', (editor, event) => {
            // تحسين استجابة الاقتراحات عند الكتابة
            const keyCode = event.keyCode;

            // تحقق من وجود :: أو -> قبل الموضع الحالي
            const cursor = editor.getCursor();
            const line = editor.getLine(cursor.line);
            const staticMethodTrigger = line.substring(0, cursor.ch).endsWith('::');
            const methodTrigger = line.substring(0, cursor.ch).endsWith('->');

            if (staticMethodTrigger || methodTrigger) {
                // إظهار الاقتراحات عند كتابة :: أو ->
                setTimeout(() => {
                    CodeMirror.showHint(editor, this.showHints.bind(this), {completeSingle: false});
                }, 100);
            } else if (!editor.state.completionActive &&
                (keyCode === 190 || // النقطة
                 keyCode === 186 || // النقطتان
                 (keyCode >= 65 && keyCode <= 90) || // الحروف الكبيرة
                 (keyCode >= 97 && keyCode <= 122))) { // الحروف الصغيرة

                // إظهار الاقتراحات عند كتابة الحروف
                setTimeout(() => {
                    CodeMirror.showHint(editor, this.showHints.bind(this), {completeSingle: false});
                }, 100);
            }
        });

        let value = localStorage.getItem('tinker-tool');

        if (typeof value === 'string') {
            this.codeEditor.setValue(value);
            this.codeEditor.execCommand('goDocEnd');
        }

        // Load available classes from the server
        this.loadAvailableClasses();

        // إضافة أنماط CSS مخصصة لتحسين مظهر المحرر والاقتراحات
        const style = document.createElement('style');
        style.textContent = `
            /* ثيم مشابه لـ VS Code الافتراضي */
            .cm-s-vscode-dark.CodeMirror {
                background-color: #1e1e1e;
                color: #d4d4d4;
            }

            .cm-s-vscode-dark .CodeMirror-gutters {
                background: #1e1e1e;
                border-right: 1px solid #333;
            }

            .cm-s-vscode-dark .CodeMirror-linenumber {
                color: #858585;
            }

            .cm-s-vscode-dark .CodeMirror-cursor {
                border-left: 1px solid #d4d4d4;
            }

            .cm-s-vscode-dark .cm-keyword {
                color: #569cd6;
            }

            .cm-s-vscode-dark .cm-operator {
                color: #d4d4d4;
            }

            .cm-s-vscode-dark .cm-variable-2 {
                color: #9cdcfe;
            }

            .cm-s-vscode-dark .cm-variable-3 {
                color: #4ec9b0;
            }

            .cm-s-vscode-dark .cm-builtin {
                color: #dcdcaa;
            }

            .cm-s-vscode-dark .cm-atom {
                color: #569cd6;
            }

            .cm-s-vscode-dark .cm-number {
                color: #b5cea8;
            }

            .cm-s-vscode-dark .cm-def {
                color: #9cdcfe;
            }

            .cm-s-vscode-dark .cm-string {
                color: #ce9178;
            }

            .cm-s-vscode-dark .cm-string-2 {
                color: #ce9178;
            }

            .cm-s-vscode-dark .cm-comment {
                color: #6a9955;
            }

            .cm-s-vscode-dark .cm-tag {
                color: #569cd6;
            }

            .cm-s-vscode-dark .cm-meta {
                color: #dcdcaa;
            }

            .cm-s-vscode-dark .cm-attribute {
                color: #9cdcfe;
            }

            .cm-s-vscode-dark .cm-property {
                color: #9cdcfe;
            }

            .cm-s-vscode-dark .cm-qualifier {
                color: #9cdcfe;
            }

            .cm-s-vscode-dark .cm-variable {
                color: #9cdcfe;
            }

            .cm-s-vscode-dark .cm-tag {
                color: #569cd6;
            }

            .cm-s-vscode-dark .cm-error {
                color: #f44747;
            }

            .cm-s-vscode-dark .CodeMirror-activeline-background {
                background: #2c2c2c;
            }

            .cm-s-vscode-dark .CodeMirror-matchingbracket {
                outline: 1px solid grey;
                color: #d4d4d4 !important;
                background-color: #3b3b3b;
            }

            /* تحسين تصميم التحديد */
            .cm-s-vscode-dark .CodeMirror-selected {
                background-color: #264f78 !important;
            }

            .cm-s-vscode-dark .CodeMirror-selectedtext {
                color: #ffffff !important;
            }

            /* تحسين تصميم الاقتراحات */
            .CodeMirror-hints {
                position: absolute;
                z-index: 1000;
                overflow: hidden;
                list-style: none;
                margin: 0;
                padding: 2px;
                border-radius: 3px;
                border: 1px solid #454545;
                background: #252526;
                font-size: 90%;
                max-height: 20em;
                overflow-y: auto;
                box-shadow: 0 4px 10px rgba(0,0,0,0.4);
            }

            .CodeMirror-hint {
                margin: 0;
                padding: 5px 10px;
                border-radius: 2px;
                white-space: pre;
                color: #d4d4d4;
                cursor: pointer;
                font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
                transition: background 0.2s ease;
            }

            li.CodeMirror-hint-active {
                background: #0e639c;
                color: #ffffff;
            }

            /* تصنيف الاقتراحات حسب النوع */
            .hint-model {
                color: #c586c0;
            }

            .hint-db {
                color: #4ec9b0;
            }

            .hint-collection {
                color: #b5cea8;
            }

            .hint-class {
                color: #4ec9b0;
            }

            .hint-facade {
                color: #dcdcaa;
            }

            .hint-keyword {
                color: #569cd6;
            }

            .CodeMirror-import-tooltip {
                background: #252526;
                color: #d4d4d4;
                padding: 5px 10px;
                border-radius: 3px;
                border: 1px solid #454545;
                font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
                font-size: 12px;
                display: inline-block;
                margin-left: 20px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            }

            .import-link {
                color: #3794ff;
                text-decoration: none;
                margin-left: 8px;
                font-weight: bold;
            }

            .import-link:hover {
                text-decoration: underline;
            }
        `;
        document.head.appendChild(style);

        // تسجيل دالة الاقتراحات المخصصة
        CodeMirror.registerHelper('hint', 'anyword', (editor, options) => {
            return this.showHints(editor, options);
        });
    },

    methods: {
        loadAvailableClasses() {
            this.isLoadingClasses = true;
            this.$emit('loading-status', true);

            // تحسين بناء عنوان URL
            let baseUrl = window.location.origin;
            let classesUrl = baseUrl + '/tinker/classes';

            // طباعة معلومات التصحيح
            console.log('Base URL:', baseUrl);
            console.log('Loading classes from:', classesUrl);

            axios.get(classesUrl)
                .then(response => {
                    if (response.data && response.data.classes) {
                        this.phpClasses = response.data.classes;
                        console.log('Loaded classes:', this.phpClasses.length);

                        if (response.data.count) {
                            console.log('Total classes:', response.data.count);
                        }

                        if (response.data.app_path) {
                            console.log('App path:', response.data.app_path);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading classes:', error);
                    console.error('Status:', error.response ? error.response.status : 'No response');
                    console.error('Message:', error.message);
                    // Fallback to default classes if API fails
                    this.setDefaultClasses();
                })
                .finally(() => {
                    this.isLoadingClasses = false;
                    this.$emit('loading-status', false);
                });
        },

        setDefaultClasses() {
            // Fallback classes if API fails
            this.phpClasses = [
                { name: 'User', namespace: 'App\\Models\\User' },
                { name: 'Auth', namespace: 'Illuminate\\Support\\Facades\\Auth' },
                { name: 'DB', namespace: 'Illuminate\\Support\\Facades\\DB' },
                { name: 'Route', namespace: 'Illuminate\\Support\\Facades\\Route' },
                { name: 'Storage', namespace: 'Illuminate\\Support\\Facades\\Storage' },
                { name: 'Hash', namespace: 'Illuminate\\Support\\Facades\\Hash' },
                { name: 'Cache', namespace: 'Illuminate\\Support\\Facades\\Cache' },
                { name: 'Session', namespace: 'Illuminate\\Support\\Facades\\Session' },
                { name: 'Validator', namespace: 'Illuminate\\Support\\Facades\\Validator' },
                { name: 'Carbon', namespace: 'Carbon\\Carbon' },
                { name: 'Str', namespace: 'Illuminate\\Support\\Str' },
                { name: 'Arr', namespace: 'Illuminate\\Support\\Arr' },
                // إضافة فئات افتراضية للوظائف والإشعارات والتعدادات
                { name: 'Job', namespace: 'Illuminate\\Bus\\Queueable' },
                { name: 'Notification', namespace: 'Illuminate\\Support\\Facades\\Notification' },
                { name: 'Dispatchable', namespace: 'Illuminate\\Foundation\\Bus\\Dispatchable' },
                { name: 'InteractsWithQueue', namespace: 'Illuminate\\Queue\\InteractsWithQueue' },
                { name: 'SerializesModels', namespace: 'Illuminate\\Queue\\SerializesModels' },
                { name: 'ShouldQueue', namespace: 'Illuminate\\Contracts\\Queue\\ShouldQueue' },
                { name: 'Mailable', namespace: 'Illuminate\\Mail\\Mailable' },
                { name: 'Notifiable', namespace: 'Illuminate\\Notifications\\Notifiable' }
            ];
        },

        executeCode() {
            let code = this.codeEditor.getValue().trim();

            if (code === '') {
                this.$emit('execute', '<error>You must type some code to execute.</error>');
                return;
            }

            axios.post(this.path, { code }).then(({ data }) => {
                this.$emit('execute', data);
            });
        },

        showHints(cm, options) {
            console.log('showHints called');

            const cursor = cm.getCursor();
            const token = cm.getTokenAt(cursor);
            const line = cm.getLine(cursor.line);
            const currentWord = token.string;

            console.log('Current token:', token);
            console.log('Current line:', line);

            // إنشاء قائمة الاقتراحات
            let list = [];

            // إضافة اقتراحات الفئات
            this.phpClasses.forEach(cls => {
                if (typeof cls === 'string') {
                    list.push({
                        text: cls,
                        displayText: cls,
                        className: 'hint-class',
                        type: 'class'
                    });
                } else if (cls.name) {
                    list.push({
                        text: cls.name,
                        displayText: cls.name,
                        className: 'hint-class',
                        type: 'class'
                    });
                }
            });

            // إضافة الكلمات المفتاحية والدوال
            this.phpKeywords.forEach(keyword => {
                list.push({
                    text: keyword,
                    displayText: keyword,
                    className: 'hint-keyword',
                    type: 'keyword'
                });
            });

            // إضافة اقتراحات النماذج
            this.contextualSuggestions.model.forEach(method => {
                list.push({
                    text: method,
                    displayText: method,
                    className: 'hint-model',
                    type: 'model'
                });
            });

            // إضافة اقتراحات قاعدة البيانات
            this.contextualSuggestions.db.forEach(method => {
                list.push({
                    text: method,
                    displayText: method,
                    className: 'hint-db',
                    type: 'db'
                });
            });

            // إضافة اقتراحات المجموعات
            this.contextualSuggestions.collection.forEach(method => {
                list.push({
                    text: method,
                    displayText: method,
                    className: 'hint-collection',
                    type: 'collection'
                });
            });

            // تصفية الاقتراحات بناءً على الكلمة الحالية
            if (currentWord && currentWord !== '::' && currentWord !== '->') {
                list = list.filter(item =>
                    item.text.toLowerCase().startsWith(currentWord.toLowerCase())
                );
            }

            console.log('Suggestions list:', list.length, 'items');

            return {
                list: list,
                from: {line: cursor.line, ch: token.start},
                to: {line: cursor.line, ch: token.end},
                // إضافة هذا الخيار لمنع الإكمال التلقائي
                completeSingle: false
            };
        },

        handleTabCompletion(cm) {
            const cursor = cm.getCursor();
            const token = cm.getTokenAt(cursor);

            if (token.type === 'variable' || token.string.match(/[a-zA-Z$_:>]/)) {
                CodeMirror.showHint(cm, this.showHints.bind(this), {completeSingle: false});
            } else {
                cm.replaceSelection('    ');
            }
        },

        checkForClassImport(editor) {
            const cursor = editor.getCursor();
            const token = editor.getTokenAt(cursor);

            // تحقق مما إذا كان المؤشر في نهاية كلمة
            const isAtWordEnd = cursor.ch === token.end;

            // تحقق مما إذا كانت الكلمة اسم فئة محتمل (يبدأ بحرف كبير)
            if (isAtWordEnd && token.type === 'variable' && token.string.match(/^[A-Z][a-zA-Z0-9_]*$/)) {
                const className = token.string;
                console.log('Found potential class name:', className);

                // تحقق مما إذا كان هذا اسم فئة معروف
                const classInfo = this.phpClasses.find(cls => cls.name === className);

                // تحقق مما إذا كانت الفئة تحتاج إلى استيراد
                if (classInfo && !this.importedClasses.has(className)) {
                    console.log('Class needs import:', classInfo);

                    // تحقق مما إذا كان السطر يحتوي بالفعل على عبارة استيراد
                    if (!this.hasImportForClass(editor, classInfo)) {
                        // إضافة الاستيراد تلقائيًا بدون عرض اقتراح
                        this.addImport(editor, classInfo);
                    }
                }
            }
        },

        hasImportForClass(editor, classInfo) {
            // تحقق مما إذا كان هناك بالفعل استيراد لهذه الفئة
            for (let i = 0; i < editor.lineCount(); i++) {
                const line = editor.getLine(i);
                if (line.includes(`use ${classInfo.namespace};`)) {
                    return true;
                }
            }
            return false;
        },

        showImportSuggestion(editor, classInfo) {
            console.log('Showing import suggestion for:', classInfo.name, classInfo.namespace);

            // إنشاء عنصر الاقتراح
            const marker = document.createElement('div');
            marker.className = 'CodeMirror-import-tooltip';
            marker.innerHTML = `Import ${classInfo.namespace}? <a href="#" class="import-link">Import</a>`;

            // إضافة الاقتراح كعلامة في المحرر
            const cursor = editor.getCursor();
            const lineHandle = editor.getLineHandle(cursor.line);

            // إضافة العلامة إلى المحرر
            const markText = editor.markText(
                { line: cursor.line, ch: 0 },
                { line: cursor.line, ch: editor.getLine(cursor.line).length },
                {
                    replacedWith: marker,
                    clearOnEnter: true,
                    handleMouseEvents: true
                }
            );

            // إضافة مستمع الحدث لرابط الاستيراد
            const importLink = marker.querySelector('.import-link');
            importLink.addEventListener('click', (e) => {
                e.preventDefault();
                this.addImport(editor, classInfo);
                markText.clear();
            });

            // إزالة العلامة بعد 5 ثوانٍ
            setTimeout(() => {
                markText.clear();
            }, 5000);
        },

        importClass(editor) {
            const cursor = editor.getCursor();
            const token = editor.getTokenAt(cursor);

            if (token.type === 'variable' && token.string.match(/^[A-Z][a-zA-Z0-9_]*$/)) {
                const className = token.string;
                const classInfo = this.phpClasses.find(cls => cls.name === className);

                if (classInfo) {
                    this.addImport(editor, classInfo);
                }
            }
        },

        addImport(editor, classInfo) {
            // تحويل classInfo إلى تنسيق موحد
            let className, namespace;

            if (typeof classInfo === 'string') {
                className = classInfo;
                // محاولة العثور على مساحة الاسم من قائمة الفئات
                const fullClassInfo = this.phpClasses.find(cls =>
                    (typeof cls !== 'string' && cls.name === classInfo)
                );

                if (fullClassInfo) {
                    namespace = fullClassInfo.namespace;
                } else {
                    // إذا لم يتم العثور على مساحة الاسم، استخدم الاسم كمساحة اسم افتراضية
                    namespace = `App\\Models\\${classInfo}`;
                }
            } else {
                className = classInfo.name;
                namespace = classInfo.namespace;
            }

            // التحقق مما إذا كنا قد استوردنا هذه الفئة بالفعل
            if (this.importedClasses.has(className)) {
                return;
            }

            // إضافة إلى مجموعة الفئات المستوردة
            this.importedClasses.add(className);

            // العثور على المكان المناسب لإدراج الاستيراد
            const importStatement = `use ${namespace};`;

            // التحقق مما إذا كانت هناك استيرادات بالفعل
            let hasImports = false;
            let lastImportLine = 0;

            for (let i = 0; i < editor.lineCount(); i++) {
                const line = editor.getLine(i);
                if (line.trim().startsWith('use ') && line.includes(';')) {
                    hasImports = true;
                    lastImportLine = i;
                }
            }

            if (hasImports) {
                // إدراج بعد آخر استيراد
                editor.replaceRange(`${importStatement}\n`,
                    { line: lastImportLine + 1, ch: 0 },
                    { line: lastImportLine + 1, ch: 0 });
                this.lastImportLine = lastImportLine + 1;
            } else {
                // إدراج في بداية الملف
                editor.replaceRange(`${importStatement}\n\n`,
                    { line: 0, ch: 0 },
                    { line: 0, ch: 0 });
                this.lastImportLine = 0;
            }

            // إظهار إشعار بالاستيراد
            this.showImportNotification(className, namespace);
        },

        // دالة جديدة للتعامل مع الكود الملصق
        handlePastedCode(editor) {
            console.log('Handling pasted code');

            // الحصول على النص الكامل
            const code = editor.getValue();

            // البحث عن أسماء الفئات في الكود
            this.findAndImportClasses(editor, code);
        },

        // دالة للبحث عن أسماء الفئات في الكود واستيرادها
        findAndImportClasses(editor, code) {
            console.log('Searching for classes in pasted code');

            // تعبير منتظم للبحث عن أسماء الفئات المحتملة
            // يبحث عن الكلمات التي تبدأ بحرف كبير وتتبعها أحرف صغيرة أو أرقام أو _
            const classNameRegex = /\b([A-Z][a-zA-Z0-9_]*)\b/g;

            // تعبير منتظم للبحث عن استخدامات الفئات مع ::
            const staticUsageRegex = /\b([A-Z][a-zA-Z0-9_]*)::/g;

            // تعبير منتظم للبحث عن استخدامات التعدادات
            const enumUsageRegex = /\b([A-Z][a-zA-Z0-9_]*)(StatusEnum|Enum|Type)\b::/g;

            // الحصول على جميع أسماء الفئات المحتملة
            let matches = [];

            // البحث عن الفئات المستخدمة بشكل عادي
            const normalMatches = code.match(classNameRegex) || [];
            matches = matches.concat(normalMatches);

            // البحث عن الفئات المستخدمة مع ::
            let staticMatch;
            while ((staticMatch = staticUsageRegex.exec(code)) !== null) {
                if (staticMatch[1]) {
                    matches.push(staticMatch[1]);
                }
            }

            // البحث عن التعدادات المستخدمة
            let enumMatch;
            while ((enumMatch = enumUsageRegex.exec(code)) !== null) {
                if (enumMatch[0]) {
                    // استخراج اسم التعداد الكامل
                    const fullEnumName = enumMatch[0].replace('::', '');
                    matches.push(fullEnumName);
                }
            }

            // إزالة التكرارات
            const uniqueClassNames = [...new Set(matches)];

            console.log('Found potential class names:', uniqueClassNames);

            // قائمة بالفئات المعروفة التي نريد استيرادها دائمًا إذا وجدت في الكود
            const knownClasses = [
                { name: 'User', namespace: 'App\\Models\\User' },
                { name: 'Auth', namespace: 'Illuminate\\Support\\Facades\\Auth' },
                { name: 'DB', namespace: 'Illuminate\\Support\\Facades\\DB' },
                { name: 'Route', namespace: 'Illuminate\\Support\\Facades\\Route' },
                { name: 'Storage', namespace: 'Illuminate\\Support\\Facades\\Storage' },
                { name: 'Hash', namespace: 'Illuminate\\Support\\Facades\\Hash' },
                { name: 'Cache', namespace: 'Illuminate\\Support\\Facades\\Cache' },
                { name: 'Session', namespace: 'Illuminate\\Support\\Facades\\Session' },
                { name: 'Validator', namespace: 'Illuminate\\Support\\Facades\\Validator' },
                { name: 'Carbon', namespace: 'Carbon\\Carbon' },
                { name: 'Str', namespace: 'Illuminate\\Support\\Str' },
                { name: 'Arr', namespace: 'Illuminate\\Support\\Arr' },
                { name: 'Log', namespace: 'Illuminate\\Support\\Facades\\Log' },
                { name: 'File', namespace: 'Illuminate\\Support\\Facades\\File' },
                { name: 'Event', namespace: 'Illuminate\\Support\\Facades\\Event' },
                { name: 'Mail', namespace: 'Illuminate\\Support\\Facades\\Mail' },
                { name: 'Notification', namespace: 'Illuminate\\Support\\Facades\\Notification' },
                { name: 'Queue', namespace: 'Illuminate\\Support\\Facades\\Queue' },
                { name: 'Schema', namespace: 'Illuminate\\Support\\Facades\\Schema' },
                { name: 'URL', namespace: 'Illuminate\\Support\\Facades\\URL' },
                { name: 'Artisan', namespace: 'Illuminate\\Support\\Facades\\Artisan' },
                { name: 'Blade', namespace: 'Illuminate\\Support\\Facades\\Blade' },
                { name: 'Config', namespace: 'Illuminate\\Support\\Facades\\Config' },
                { name: 'Cookie', namespace: 'Illuminate\\Support\\Facades\\Cookie' },
                { name: 'Crypt', namespace: 'Illuminate\\Support\\Facades\\Crypt' },
                { name: 'Date', namespace: 'Illuminate\\Support\\Facades\\Date' },
                { name: 'Http', namespace: 'Illuminate\\Support\\Facades\\Http' },
                { name: 'Password', namespace: 'Illuminate\\Support\\Facades\\Password' },
                { name: 'Redirect', namespace: 'Illuminate\\Support\\Facades\\Redirect' },
                { name: 'Request', namespace: 'Illuminate\\Http\\Request' },
                { name: 'Response', namespace: 'Illuminate\\Http\\Response' },
                { name: 'Collection', namespace: 'Illuminate\\Support\\Collection' },
                // إضافة فئات Laravel الشائعة الأخرى
                { name: 'Job', namespace: 'Illuminate\\Bus\\Queueable' },
                { name: 'Mailable', namespace: 'Illuminate\\Mail\\Mailable' },
                { name: 'Notifiable', namespace: 'Illuminate\\Notifications\\Notifiable' },
                { name: 'ShouldQueue', namespace: 'Illuminate\\Contracts\\Queue\\ShouldQueue' },
                { name: 'Dispatchable', namespace: 'Illuminate\\Foundation\\Bus\\Dispatchable' },
                { name: 'InteractsWithQueue', namespace: 'Illuminate\\Queue\\InteractsWithQueue' },
                { name: 'SerializesModels', namespace: 'Illuminate\\Queue\\SerializesModels' }
            ];

            // التحقق من كل اسم فئة محتمل
            uniqueClassNames.forEach(className => {
                // تجاهل الكلمات المحجوزة في PHP
                if (['Class', 'Interface', 'Trait', 'Function', 'Array', 'String', 'Int', 'Float', 'Bool', 'True', 'False', 'Null', 'Self', 'Parent', 'Static', 'Public', 'Private', 'Protected', 'Final', 'Abstract', 'Extends', 'Implements'].includes(className)) {
                    return;
                }

                // التعامل مع التعدادات بشكل خاص
                if (className.endsWith('Enum') || className.endsWith('StatusEnum') || className.endsWith('Type')) {
                    console.log('Found potential enum:', className);
                    const enumInfo = { name: className, namespace: `App\\Enums\\${className}` };

                    // التحقق مما إذا كانت التعداد مستورد بالفعل
                    if (!this.hasImportForClass(editor, enumInfo)) {
                        // استيراد التعداد
                        this.addImport(editor, enumInfo);
                    }
                    return;
                }

                // البحث أولاً في قائمة الفئات المعروفة
                const knownClass = knownClasses.find(cls => cls.name === className);
                if (knownClass) {
                    console.log('Found known class to import:', className);

                    // التحقق مما إذا كانت الفئة مستوردة بالفعل
                    if (!this.hasImportForClass(editor, knownClass)) {
                        // استيراد الفئة
                        this.addImport(editor, knownClass);
                    }
                    return;
                }

                // البحث عن الفئة في قائمة الفئات المتاحة
                const classInfo = this.phpClasses.find(cls =>
                    (typeof cls === 'string' && cls === className) ||
                    (cls.name && cls.name === className)
                );

                if (classInfo) {
                    console.log('Found class to import:', className);

                    // التحقق مما إذا كانت الفئة مستوردة بالفعل
                    if (!this.hasImportForClass(editor, classInfo)) {
                        // استيراد الفئة
                        this.addImport(editor, classInfo);
                    }
                } else {
                    // محاولة تخمين مساحة الاسم بناءً على الاتفاقيات الشائعة في Laravel
                    this.guessAndImportNamespace(editor, className);
                }
            });

            // البحث عن استخدامات محددة في الكود
            this.checkForSpecificUsages(editor, code);
        },

        // دالة جديدة لتخمين مساحة الاسم بناءً على اسم الفئة
        guessAndImportNamespace(editor, className) {
            // قائمة بالمسارات المحتملة للفئات في Laravel
            const possiblePaths = [
                { suffix: 'Job', namespace: `App\\Jobs\\${className}` },
                { suffix: 'Notification', namespace: `App\\Notifications\\${className}` },
                { suffix: 'Mail', namespace: `App\\Mail\\${className}` },
                { suffix: 'Event', namespace: `App\\Events\\${className}` },
                { suffix: 'Listener', namespace: `App\\Listeners\\${className}` },
                { suffix: 'Policy', namespace: `App\\Policies\\${className}` },
                { suffix: 'Rule', namespace: `App\\Rules\\${className}` },
                { suffix: 'Resource', namespace: `App\\Http\\Resources\\${className}` },
                { suffix: 'Request', namespace: `App\\Http\\Requests\\${className}` },
                { suffix: 'Controller', namespace: `App\\Http\\Controllers\\${className}` },
                { suffix: 'Middleware', namespace: `App\\Http\\Middleware\\${className}` },
                { suffix: 'Provider', namespace: `App\\Providers\\${className}` },
                { suffix: 'Enum', namespace: `App\\Enums\\${className}` },
                { suffix: 'StatusEnum', namespace: `App\\Enums\\${className}` },
                { suffix: 'Type', namespace: `App\\Enums\\${className}` }
            ];

            // التحقق مما إذا كان اسم الفئة ينتهي بأحد اللواحق المعروفة
            for (const path of possiblePaths) {
                if (className.endsWith(path.suffix)) {
                    const classInfo = { name: className, namespace: path.namespace };
                    console.log('Guessing namespace for:', className, path.namespace);

                    // التحقق مما إذا كانت الفئة مستوردة بالفعل
                    if (!this.hasImportForClass(editor, classInfo)) {
                        // استيراد الفئة
                        this.addImport(editor, classInfo);
                    }
                    return;
                }
            }

            // إذا لم يكن هناك لاحقة معروفة، نفترض أنها قد تكون نموذجًا
            const modelInfo = { name: className, namespace: `App\\Models\\${className}` };
            console.log('Assuming model namespace for:', className);

            // التحقق مما إذا كانت الفئة مستوردة بالفعل
            if (!this.hasImportForClass(editor, modelInfo)) {
                // استيراد الفئة
                this.addImport(editor, modelInfo);
            }
        },

        // دالة جديدة للبحث عن استخدامات محددة في الكود
        checkForSpecificUsages(editor, code) {
            // البحث عن استخدام Carbon
            if (code.includes('Carbon') || code.includes('now()') || code.includes('today()') || code.includes('yesterday()') || code.includes('tomorrow()')) {
                const carbonClass = { name: 'Carbon', namespace: 'Carbon\\Carbon' };
                if (!this.hasImportForClass(editor, carbonClass)) {
                    this.addImport(editor, carbonClass);
                }
            }

            // البحث عن استخدام DB
            if (code.includes('DB::') || code.includes('table(') || code.includes('select(') || code.includes('where(') || code.includes('join(')) {
                const dbClass = { name: 'DB', namespace: 'Illuminate\\Support\\Facades\\DB' };
                if (!this.hasImportForClass(editor, dbClass)) {
                    this.addImport(editor, dbClass);
                }
            }

            // البحث عن استخدام Auth
            if (code.includes('Auth::') || code.includes('auth()->') || code.includes('user()') || code.includes('check()') || code.includes('attempt(')) {
                const authClass = { name: 'Auth', namespace: 'Illuminate\\Support\\Facades\\Auth' };
                if (!this.hasImportForClass(editor, authClass)) {
                    this.addImport(editor, authClass);
                }
            }

            // البحث عن استخدام Str
            if (code.includes('Str::') || code.includes('str_') || code.includes('->str') || code.includes('string manipulation')) {
                const strClass = { name: 'Str', namespace: 'Illuminate\\Support\\Str' };
                if (!this.hasImportForClass(editor, strClass)) {
                    this.addImport(editor, strClass);
                }
            }

            // البحث عن استخدام وظائف الوظائف (Jobs)
            if (code.includes('dispatch(') || code.includes('dispatchNow(') || code.includes('dispatchSync(') || code.includes('dispatchAfter(')) {
                const jobClass = { name: 'Job', namespace: 'Illuminate\\Bus\\Queueable' };
                if (!this.hasImportForClass(editor, jobClass)) {
                    this.addImport(editor, jobClass);
                }

                const dispatchableClass = { name: 'Dispatchable', namespace: 'Illuminate\\Foundation\\Bus\\Dispatchable' };
                if (!this.hasImportForClass(editor, dispatchableClass)) {
                    this.addImport(editor, dispatchableClass);
                }
            }

            // البحث عن استخدام الإشعارات (Notifications)
            if (code.includes('notify(') || code.includes('notification') || code.includes('Notification::')) {
                const notificationClass = { name: 'Notification', namespace: 'Illuminate\\Support\\Facades\\Notification' };
                if (!this.hasImportForClass(editor, notificationClass)) {
                    this.addImport(editor, notificationClass);
                }
            }

            // البحث عن استخدام التعدادات (Enums)
            const enumRegex = /\b([A-Z][a-zA-Z0-9_]*)(StatusEnum|Enum|Type)::/g;
            let enumMatch;
            while ((enumMatch = enumRegex.exec(code)) !== null) {
                if (enumMatch[0]) {
                    const enumName = enumMatch[0].replace('::', '');
                    console.log('Found enum usage:', enumName);
                    const enumInfo = { name: enumName, namespace: `App\\Enums\\${enumName}` };
                    if (!this.hasImportForClass(editor, enumInfo)) {
                        this.addImport(editor, enumInfo);
                    }
                }
            }
        },

        // دالة جديدة لإظهار إشعار بالاستيراد
        showImportNotification(className, namespace) {
            console.log(`Imported ${className} from ${namespace}`);

            // يمكن إضافة إشعار مرئي هنا إذا كنت ترغب في ذلك
            // على سبيل المثال، يمكن إضافة عنصر div مؤقت في أعلى المحرر
        }
    }
};
</script>

<style src="codemirror/lib/codemirror.css" />
<style src="codemirror/theme/idea.css" />
<style>
.CodeMirror {
    height: 100%;
    width: 100%;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 14px;
    line-height: 1.5;
}

.input {
    height: 100%;
    width: 100%;
}

.CodeMirror-hints {
    position: absolute;
    z-index: 1000;
    overflow: hidden;
    list-style: none;
    margin: 0;
    padding: 2px;
    border-radius: 5px;
    border: 1px solid #44475a;
    background: #282a36;
    font-size: 90%;
    max-height: 20em;
    overflow-y: auto;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.CodeMirror-hint {
    margin: 0;
    padding: 5px 10px;
    border-radius: 3px;
    white-space: pre;
    color: #f8f8f2;
    cursor: pointer;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    transition: background 0.2s ease;
}

li.CodeMirror-hint-active {
    background: #6272a4;
    color: #f8f8f2;
}

/* تصنيف الاقتراحات حسب النوع */
.hint-model {
    color: #ff79c6;
}

.hint-db {
    color: #8be9fd;
}

.hint-collection {
    color: #50fa7b;
}

.hint-class {
    color: #bd93f9;
}

.hint-facade {
    color: #ffb86c;
}

.CodeMirror-import-tooltip {
    background: rgba(44, 62, 80, 0.9);
    color: white;
    padding: 5px 10px;
    border-radius: 3px;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 12px;
    display: inline-block;
    margin-left: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.import-link {
    color: #2ecc71;
    text-decoration: none;
    margin-left: 8px;
    font-weight: bold;
}

.import-link:hover {
    text-decoration: underline;
}
</style>
