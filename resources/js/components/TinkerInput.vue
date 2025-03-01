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
                    this.showHints(cm);
                },
                'Tab': (cm) => {
                    if (cm.somethingSelected()) {
                        cm.indentSelection('add');
                    } else {
                        this.handleTabCompletion(cm);
                    }
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
    },

    methods: {
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

            const list = this.phpKeywords.filter(item =>
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
</style>
