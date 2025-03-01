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
        isLoadingClasses: false
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
                    this.showHints(cm);
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
            theme: 'dracula',
            matchBrackets: true,
            autoCloseBrackets: true,
            styleActiveLine: true,
            indentUnit: 4
        };

        this.codeEditor = CodeMirror.fromTextArea(this.$refs.codeEditor, config);

        this.codeEditor.on('change', editor => {
            localStorage.setItem('tinker-tool', editor.getValue());
            this.autoShowHints(editor);
            this.checkForClassImport(editor);
        });

        this.codeEditor.on('keyup', (editor, event) => {
            if (!editor.state.completionActive &&
                /[a-zA-Z$_:>]/.test(String.fromCharCode(event.keyCode))) {
                this.showHints(editor);
            }
        });

        let value = localStorage.getItem('tinker-tool');

        if (typeof value === 'string') {
            this.codeEditor.setValue(value);
            this.codeEditor.execCommand('goDocEnd');
        }

        // Load available classes from the server
        this.loadAvailableClasses();
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
                { name: 'Arr', namespace: 'Illuminate\\Support\\Arr' }
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

        showHints(editor) {
            const cursor = editor.getCursor();
            const token = editor.getTokenAt(cursor);
            const line = editor.getLine(cursor.line);
            const start = token.start;
            const end = cursor.ch;
            const currentWord = line.slice(start, end).toLowerCase();

            console.log('Current word:', currentWord);
            console.log('Token:', token);

            // تحليل السياق الحالي
            const context = this.analyzeContext(editor);
            console.log('Context:', context);

            let list = [];

            // إذا كان هناك سياق محدد (مثل User:: أو DB::)
            if (context.type === 'model') {
                // اقتراحات خاصة بالنماذج
                list = this.contextualSuggestions.model.map(item => ({
                    text: item,
                    displayText: item,
                    className: 'hint-model',
                    render: (element, self, data) => {
                        element.innerHTML = `<span class="hint-model">${data.displayText}</span>`;
                    }
                }));
                console.log('Showing model suggestions:', list.length);
            }
            else if (context.type === 'db') {
                // اقتراحات خاصة بقاعدة البيانات
                list = this.contextualSuggestions.db.map(item => ({
                    text: item,
                    displayText: item,
                    className: 'hint-db',
                    render: (element, self, data) => {
                        element.innerHTML = `<span class="hint-db">${data.displayText}</span>`;
                    }
                }));
                console.log('Showing DB suggestions:', list.length);
            }
            else if (context.type === 'collection') {
                // اقتراحات خاصة بالمجموعات
                list = this.contextualSuggestions.collection.map(item => ({
                    text: item,
                    displayText: item,
                    className: 'hint-collection',
                    render: (element, self, data) => {
                        element.innerHTML = `<span class="hint-collection">${data.displayText}</span>`;
                    }
                }));
                console.log('Showing collection suggestions:', list.length);
            }
            else {
                // اقتراحات عامة (فقط إذا لم يكن هناك سياق محدد)

                // إضافة الفئات
                this.phpClasses.forEach(cls => {
                    list.push({
                        text: cls.name,
                        displayText: cls.name,
                        className: 'hint-class',
                        render: (element, self, data) => {
                            element.innerHTML = `<span class="hint-class">${data.displayText}</span>`;
                        }
                    });
                });

                // إضافة الكلمات المفتاحية
                this.phpKeywords.forEach(keyword => {
                    let type = 'default';
                    if (keyword.includes('::')) {
                        type = 'facade';
                    } else if (keyword.includes('->')) {
                        type = 'collection';
                    } else if (keyword.includes('(')) {
                        type = 'db';
                    }

                    list.push({
                        text: keyword,
                        displayText: keyword,
                        className: `hint-${type}`,
                        render: (element, self, data) => {
                            element.innerHTML = `<span class="hint-${type}">${data.displayText}</span>`;
                        }
                    });
                });

                console.log('Showing general suggestions:', list.length);
            }

            // تصفية الاقتراحات بناءً على الكلمة الحالية
            if (currentWord) {
                list = list.filter(item =>
                    item.text.toLowerCase().includes(currentWord)
                );
                console.log('Filtered suggestions:', list.length);
            }

            // عرض الاقتراحات فقط إذا كان هناك اقتراحات
            if (list.length > 0) {
                editor.showHint({
                    completeSingle: false,
                    hint: () => ({
                        list: list,
                        from: CodeMirror.Pos(cursor.line, start),
                        to: CodeMirror.Pos(cursor.line, end)
                    })
                });
            }
        },

        // تحليل السياق الحالي لتحديد نوع الاقتراحات المناسبة
        analyzeContext(editor) {
            const cursor = editor.getCursor();
            const line = editor.getLine(cursor.line);
            const lineUntilCursor = line.substring(0, cursor.ch);

            console.log('Analyzing context for line:', lineUntilCursor);

            // البحث عن نمط "::" (مثل User::)
            const staticMatch = lineUntilCursor.match(/(\w+)\s*::$/);
            if (staticMatch) {
                const className = staticMatch[1];
                console.log('Found static match:', className);

                // تحقق من أنواع خاصة مثل DB, Auth, etc.
                const specialFacades = ['DB', 'Auth', 'Cache', 'Config', 'Route', 'Session', 'Storage', 'Hash', 'Validator', 'Event', 'Log'];
                if (specialFacades.includes(className)) {
                    console.log('Detected special facade:', className);
                    return { type: 'db', objectName: className };
                }

                // تحقق مما إذا كان اسم فئة (يبدأ بحرف كبير)
                if (/^[A-Z]/.test(className)) {
                    console.log('Detected model class:', className);
                    return { type: 'model', objectName: className };
                }
            }

            // البحث عن نمط "->" (مثل $users->)
            const arrowMatch = lineUntilCursor.match(/(\$\w+|\w+)\s*->$/);
            if (arrowMatch) {
                const variableName = arrowMatch[1];
                console.log('Found arrow match:', variableName);

                // تحقق من السياق السابق لتحديد نوع المتغير
                const contextType = this.determineVariableType(editor, variableName);
                console.log('Determined variable type:', contextType);
                return { type: contextType, objectName: variableName };
            }

            // البحث عن نمط "." (مثل $array.)
            const dotMatch = lineUntilCursor.match(/(\w+)\s*\.$/);
            if (dotMatch) {
                console.log('Found dot match:', dotMatch[1]);
                return { type: 'collection', objectName: dotMatch[1] };
            }

            // لا يوجد سياق محدد
            console.log('No specific context detected');
            return { type: null, objectName: null };
        },

        // تحديد نوع المتغير بناءً على السياق
        determineVariableType(editor, variableName) {
            // البحث عن تعريف المتغير في النص
            const content = editor.getValue();

            // تنظيف اسم المتغير (إزالة $ إذا كان موجودًا)
            const cleanVarName = variableName.replace(/^\$/, '');

            console.log('Determining type for variable:', cleanVarName);

            // تحقق مما إذا كان المتغير هو نتيجة استدعاء نموذج
            const modelPatterns = [
                new RegExp(`\\$?${cleanVarName}\\s*=\\s*\\w+::(all|get|find|where|first)`, 'i'),
                new RegExp(`\\$?${cleanVarName}\\s*=\\s*\\w+::where`, 'i'),
                new RegExp(`\\$?${cleanVarName}\\s*=\\s*\\$?\\w+->where`, 'i')
            ];

            for (const pattern of modelPatterns) {
                if (pattern.test(content)) {
                    console.log('Variable is a collection (from model)');
                    return 'collection';
                }
            }

            // تحقق مما إذا كان المتغير هو نتيجة استدعاء DB
            const dbPatterns = [
                new RegExp(`\\$?${cleanVarName}\\s*=\\s*DB::(table|select)`, 'i'),
                new RegExp(`\\$?${cleanVarName}\\s*=\\s*\\$?\\w+->join`, 'i'),
                new RegExp(`\\$?${cleanVarName}\\s*=\\s*\\$?\\w+->select`, 'i')
            ];

            for (const pattern of dbPatterns) {
                if (pattern.test(content)) {
                    console.log('Variable is a DB query');
                    return 'db';
                }
            }

            // تحقق مما إذا كان المتغير هو كائن نموذج فردي
            const singleModelPatterns = [
                new RegExp(`\\$?${cleanVarName}\\s*=\\s*\\w+::find\\(`, 'i'),
                new RegExp(`\\$?${cleanVarName}\\s*=\\s*\\w+::findOrFail\\(`, 'i'),
                new RegExp(`\\$?${cleanVarName}\\s*=\\s*\\w+::first\\(`, 'i'),
                new RegExp(`\\$?${cleanVarName}\\s*=\\s*new\\s+\\w+\\(`, 'i')
            ];

            for (const pattern of singleModelPatterns) {
                if (pattern.test(content)) {
                    console.log('Variable is a model instance');
                    return 'model';
                }
            }

            // افتراضيًا، اعتبره مجموعة
            console.log('Variable type not determined, defaulting to collection');
            return 'collection';
        },

        autoShowHints(editor) {
            const cursor = editor.getCursor();
            const line = editor.getLine(cursor.line);
            const lineUntilCursor = line.substring(0, cursor.ch);

            // عرض الاقتراحات تلقائيًا عند كتابة "::" أو "->"
            if (lineUntilCursor.endsWith('::') || lineUntilCursor.endsWith('->')) {
                console.log('Auto showing hints for :: or ->');
                setTimeout(() => {
                    this.showHints(editor);
                }, 10);
                return;
            }
        },

        handleTabCompletion(cm) {
            if (cm.state.completionActive) {
                return CodeMirror.Pass;
            }
            const cursor = cm.getCursor();
            const token = cm.getTokenAt(cursor);

            if (token.type === 'variable' || token.string.match(/[a-zA-Z$_:>]/)) {
                this.showHints(cm);
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
            // Check if we already imported this class
            if (this.importedClasses.has(classInfo.name)) {
                return;
            }

            // Add to imported classes set
            this.importedClasses.add(classInfo.name);

            // Find where to insert the import
            const importStatement = `use ${classInfo.namespace};`;

            // Check if there are already imports
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
                // Insert after the last import
                editor.replaceRange(`${importStatement}\n`,
                    { line: lastImportLine + 1, ch: 0 },
                    { line: lastImportLine + 1, ch: 0 });
                this.lastImportLine = lastImportLine + 1;
            } else {
                // Insert at the beginning of the file
                editor.replaceRange(`${importStatement}\n\n`,
                    { line: 0, ch: 0 },
                    { line: 0, ch: 0 });
                this.lastImportLine = 0;
            }
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
