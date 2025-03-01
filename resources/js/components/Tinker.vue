<template>
    <div class="tinker">
        <div class="tinker__status" v-if="isLoading">
            <span class="tinker__status-text">Loading classes...</span>
        </div>
        <div class="split-view" :style="gridStyle">
            <TinkerInput
                :path="path"
                @execute="executeCode"
                ref="tinkerInput"
                @loading-status="updateLoadingStatus"
            />
            <div class="gutter" ref="gutter"></div>
            <div class="output" v-html="output"></div>
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
        isLoading: false
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
            };
        },
    },

    methods: {
        executeCode(output) {
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
}

.input, .output {
    overflow: auto;
    height: 100%;
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
    background-color: #1a202c;
    color: #e2e8f0;
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
</style>
