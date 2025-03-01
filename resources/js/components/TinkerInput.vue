<template>
    <section class="input"><textarea ref="codeEditor" /></section>
</template>

<script>
import 'codemirror/mode/php/php';
import 'codemirror/addon/hint/show-hint';
import 'codemirror/addon/hint/anyword-hint';
import 'codemirror/addon/hint/show-hint.css';

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
            mode: 'text/x-php',
            tabSize: 4,
            theme: 'tinker'
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

            // Get the base URL from the path prop
            const baseUrl = this.path.substring(0, this.path.lastIndexOf('/'));
            const classesUrl = `${baseUrl}/classes`;

            axios.get(classesUrl)
                .then(response => {
                    if (response.data && response.data.classes) {
                        this.phpClasses = response.data.classes;
                        console.log('Loaded classes:', this.phpClasses.length);
                    }
                })
                .catch(error => {
                    console.error('Error loading classes:', error);
                    // Fallback to default classes if API fails
                    this.setDefaultClasses();
                })
                .finally(() => {
                    this.isLoadingClasses = false;
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

            // Combine keywords and class names for suggestions
            let suggestions = [...this.phpKeywords];
            this.phpClasses.forEach(cls => {
                suggestions.push(cls.name);
            });

            const list = suggestions.filter(item =>
                item.toLowerCase().includes(currentWord)
            );

            editor.showHint({
                completeSingle: false,
                hint: () => ({
                    list: list,
                    from: CodeMirror.Pos(cursor.line, start),
                    to: CodeMirror.Pos(cursor.line, end)
                })
            });
        },

        autoShowHints(editor) {
            const cursor = editor.getCursor();
            const token = editor.getTokenAt(cursor);

            if (token.type === 'variable' || token.string.match(/[a-zA-Z$_:>]/)) {
                this.showHints(editor);
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

            // Check if we just typed a class name
            if (token.type === 'variable' && token.string.match(/^[A-Z][a-zA-Z0-9_]*$/)) {
                const className = token.string;

                // Find if this is a known class that needs import
                const classInfo = this.phpClasses.find(cls => cls.name === className);

                if (classInfo && !this.importedClasses.has(className)) {
                    // Show import suggestion
                    this.showImportSuggestion(editor, classInfo);
                }
            }
        },

        showImportSuggestion(editor, classInfo) {
            // Create a marker to show the import suggestion
            const marker = document.createElement('div');
            marker.className = 'import-suggestion';
            marker.innerHTML = `<span>Import ${classInfo.namespace}?</span> <button class="import-btn">Import</button>`;

            // Add the marker to the editor
            editor.addWidget(editor.getCursor(), marker, false);

            // Add event listener to the import button
            const importBtn = marker.querySelector('.import-btn');
            importBtn.addEventListener('click', () => {
                this.addImport(editor, classInfo);
                marker.remove();
            });

            // Remove the marker after 5 seconds
            setTimeout(() => {
                if (marker.parentNode) {
                    marker.remove();
                }
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
.CodeMirror-hints {
    position: absolute;
    z-index: 1000;
    overflow: hidden;
    list-style: none;
    margin: 0;
    padding: 2px;
    border-radius: 3px;
    border: 1px solid silver;
    background: white;
    font-size: 90%;
    max-height: 20em;
    overflow-y: auto;
}

.CodeMirror-hint {
    margin: 0;
    padding: 0 4px;
    border-radius: 2px;
    white-space: pre;
    color: black;
    cursor: pointer;
}

li.CodeMirror-hint-active {
    background: #08f;
    color: white;
}

.import-suggestion {
    position: absolute;
    background: #f0f0f0;
    border: 1px solid #ccc;
    border-radius: 3px;
    padding: 5px 10px;
    font-size: 12px;
    z-index: 1000;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.import-suggestion span {
    margin-right: 10px;
}

.import-btn {
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 3px;
    padding: 2px 8px;
    cursor: pointer;
    font-size: 12px;
}

.import-btn:hover {
    background: #45a049;
}
</style>
