<template>
    <div class="tinker">
        <div class="tinker__status" v-if="isLoading">
            <span class="tinker__status-text">Loading classes...</span>
        </div>
        <div class="tinker__shortcuts">
            <div class="shortcuts-toggle" @click="toggleShortcuts">
                <i class="shortcuts-icon">⌨️</i> الاختصارات
            </div>
            <div class="shortcuts-panel" v-if="showShortcuts">
                <div class="shortcuts-header">
                    <h3>اختصارات لوحة المفاتيح</h3>
                    <span class="shortcuts-close" @click="toggleShortcuts">×</span>
                </div>
                <div class="shortcuts-content">
                    <div class="shortcut-item">
                        <span class="shortcut-key">Ctrl+Enter / Cmd+Enter</span>
                        <span class="shortcut-desc">تنفيذ الكود</span>
                    </div>
                    <div class="shortcut-item">
                        <span class="shortcut-key">Ctrl+Space</span>
                        <span class="shortcut-desc">إظهار الاقتراحات</span>
                    </div>
                    <div class="shortcut-item">
                        <span class="shortcut-key">Tab</span>
                        <span class="shortcut-desc">إكمال الكود أو إضافة مسافات</span>
                    </div>
                    <div class="shortcut-item">
                        <span class="shortcut-key">Alt+I</span>
                        <span class="shortcut-desc">استيراد الفئة الحالية</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="tinker__container">
            <tinker-input
                :path="path"
                @execute="execute"
                @loading-status="updateLoadingStatus"
                ref="tinkerInput"
            />
            <tinker-output :output="output" />
        </div>
    </div>
</template>

<script>
import TinkerInput from './TinkerInput';
import TinkerOutput from './TinkerOutput';
import Split from 'split-grid';
import DOMPurify from 'dompurify';

export default {
    components: {
        TinkerInput,
        TinkerOutput,
    },

    props: ['path'],

    data: () => ({
        windowWidth: window.innerWidth,
        gutterWidth: 9,
        minSize: 100,
        breakpoint: 768,
        input: '',
        output: '<span class="text-dimmed">// Use cmd+enter or ctrl+enter to run.</span>',
        isLoading: false,
        showShortcuts: false
    }),

    computed: {
        columnPercentage() {
            return ((1 - this.gutterWidth / window.innerWidth) / 2) * 100 + '%';
        },

        rowPercentage() {
            return ((1 - this.gutterWidth / window.innerHeight) / 2) * 100 + '%';
        },

        needsColumnLayout() {
            return this.windowWidth > this.breakpoint;
        },

        gridStyle() {
            return {
                gridTemplateColumns: `1fr ${this.gutterWidth}px 1fr`,
                width: '100%'
            };
        },
    },

    methods: {
        execute(output) {
            this.output = DOMPurify.sanitize(output);
        },

        updateLoadingStatus(status) {
            this.isLoading = status;
        },

        initSplit() {
            this.destroySplit();

            this.split = Split({
                columnGutters: [
                    {
                        track: 1,
                        element: this.$refs.gutter,
                    },
                ],
                minSize: this.minSize,
            });
        },

        destroySplit() {
            if (this.split) {
                this.split.destroy();
            }
        },

        toggleShortcuts() {
            this.showShortcuts = !this.showShortcuts;
        }
    },

    mounted() {
        this.initSplit();

        this.$watch('needsColumnLayout', this.initSplit);

        window.addEventListener('resize', () => {
            this.windowWidth = window.innerWidth;
        });
    },
};
</script>

<style>
.tinker {
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.tinker__status {
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    color: #4a5568;
}

.tinker__status-text {
    display: inline-block;
    margin-left: 0.5rem;
}

.split-view {
    flex: 1;
    display: grid;
    height: calc(100vh - 2rem);
    width: 100%;
    overflow: hidden;
}

.input, .output {
    overflow: auto;
    height: 100%;
    width: 100%;
}

.input {
    position: relative;
}

.output {
    padding: 1rem;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.875rem;
    line-height: 1.5;
    white-space: pre-wrap;
    background-color: #282a36;
    color: #f8f8f2;
    overflow-y: auto;
    border-left: 1px solid #44475a;
    width: 100%;
    box-sizing: border-box;
}

.output pre {
    margin: 0;
    padding: 0;
    font-family: inherit;
}

.output .string {
    color: #f1fa8c;
}

.output .number {
    color: #bd93f9;
}

.output .boolean {
    color: #ff79c6;
}

.output .null {
    color: #6272a4;
}

.output .key {
    color: #8be9fd;
}

.gutter {
    background-color: #e2e8f0;
    background-repeat: no-repeat;
    background-position: 50%;
}

.gutter:hover {
    background-color: #cbd5e0;
}

.gutter.gutter-horizontal {
    cursor: col-resize;
    background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAeCAYAAADkftS9AAAAIklEQVQoU2M4c+bMfxAGAgYYmwGrIIiDjrELjpo5aiZeMwF+yNnOs5KSvgAAAABJRU5ErkJggg==');
}

.gutter.gutter-vertical {
    cursor: row-resize;
    background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAFAQMAAABo7865AAAABlBMVEVHcEzMzMzyAv2sAAAAAXRSTlMAQObYZgAAABBJREFUeF5jOAMEEAIEEFwAn3kMwcB6I2AAAAAASUVORK5CYII=');
}

.text-dimmed {
    color: #a0aec0;
}

.error {
    color: #f56565;
}

.tinker {
    display: flex;
    flex-direction: column;
    height: 100%;
    position: relative;
}

.tinker__container {
    display: flex;
    flex-direction: row;
    height: 100%;
}

.tinker__status {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    background-color: #2c3e50;
    color: white;
    padding: 8px 16px;
    text-align: center;
    z-index: 100;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 14px;
    border-bottom: 1px solid #34495e;
}

.tinker__shortcuts {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 1000;
}

.shortcuts-toggle {
    background-color: #34495e;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    transition: background-color 0.2s;
}

.shortcuts-toggle:hover {
    background-color: #2c3e50;
}

.shortcuts-icon {
    margin-right: 5px;
    font-style: normal;
}

.shortcuts-panel {
    position: absolute;
    top: 35px;
    right: 0;
    width: 350px;
    background-color: #2c3e50;
    border-radius: 5px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.3);
    color: white;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    overflow: hidden;
}

.shortcuts-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background-color: #34495e;
    border-bottom: 1px solid #3d566e;
}

.shortcuts-header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: normal;
}

.shortcuts-close {
    cursor: pointer;
    font-size: 20px;
    line-height: 1;
}

.shortcuts-content {
    padding: 10px 15px;
    max-height: 300px;
    overflow-y: auto;
}

.shortcut-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 12px;
}

.shortcut-key {
    background-color: #34495e;
    padding: 3px 6px;
    border-radius: 3px;
    font-weight: bold;
}

.shortcut-desc {
    color: #ecf0f1;
}

@media (max-width: 768px) {
    .tinker__container {
        flex-direction: column;
    }

    .shortcuts-panel {
        width: 280px;
    }
}
</style>
