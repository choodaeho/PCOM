<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue:  { type: String,  default: '' },
  placeholder: { type: String,  default: '내용을 입력하세요...' },
  minHeight:   { type: String,  default: '320px' },
})
const emit = defineEmits(['update:modelValue'])

const toolbarRef = ref(null)
const editorRef  = ref(null)
const showEmoji  = ref(false)
const showVideo  = ref(false)
const videoInput = ref('')
const uploading  = ref(false)

let quill       = null
let isInternal  = false   // 내부 업데이트 루프 방지

/* ── 이모지 목록 ───────────────────────────────────────── */
const emojiRows = [
  ['😀','😁','😂','🤣','😍','🥰','😎','🤔','😮','😱'],
  ['😭','😤','😡','🤬','🥺','😢','😅','🤦','🤷','💀'],
  ['👍','👎','👏','🙌','🤝','💪','🫡','🙏','✌️','🫶'],
  ['❤️','🔥','💯','⚡','💡','✅','❌','⭐','🎉','🎯'],
  ['🇰🇷','📢','📣','🗣️','⚔️','🏆','🥇','📌','📎','🔍'],
  ['😸','🐱','🐶','🦊','🐻','🐼','🦁','🐯','🦅','🕊️'],
]

/* ── 이미지 업로드 ──────────────────────────────────────── */
const handleImageUpload = () => {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/jpeg,image/png,image/gif,image/webp'
  input.multiple = true
  input.onchange = async () => {
    for (const file of Array.from(input.files ?? [])) {
      if (file.size > 5 * 1024 * 1024) {
        alert(`"${file.name}" 파일이 5 MB를 초과합니다.`)
        continue
      }
      uploading.value = true
      const fd = new FormData()
      fd.append('image', file)
      try {
        const res = await window.axios.post('/posts/upload-image', fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        const { url } = res.data
        const range = quill.getSelection(true)
        quill.insertEmbed(range.index, 'image', url, 'user')
        quill.setSelection(range.index + 1)
      } catch (err) {
        const msg = err?.response?.data?.message ?? '이미지 업로드에 실패했습니다. 다시 시도해주세요.'
        alert(msg)
      } finally {
        uploading.value = false
      }
    }
  }
  input.click()
}

/* ── 동영상 임베드 ──────────────────────────────────────── */
const openVideoModal = () => {
  videoInput.value = ''
  showVideo.value  = true
  nextTick(() => document.getElementById('qe-video-input')?.focus())
}

const embedVideo = () => {
  const raw = videoInput.value.trim()
  if (!raw) return

  let embedUrl = raw

  // YouTube: watch URL 또는 youtu.be 단축 URL → embed URL로 변환
  const yt = raw.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/)
  if (yt) embedUrl = `https://www.youtube.com/embed/${yt[1]}`

  // Vimeo
  const vm = raw.match(/vimeo\.com\/(\d+)/)
  if (vm) embedUrl = `https://player.vimeo.com/video/${vm[1]}`

  const range = quill.getSelection(true)
  quill.insertEmbed(range.index, 'video', embedUrl, 'user')
  quill.setSelection(range.index + 1)
  showVideo.value = false
}

/* ── 이모지 삽입 ────────────────────────────────────────── */
const insertEmoji = (emoji) => {
  const range = quill.getSelection(true)
  quill.insertText(range.index, emoji, 'user')
  quill.setSelection(range.index + emoji.length)
  showEmoji.value = false
}

/* ── 이모지 피커 외부 클릭 닫기 ────────────────────────── */
const handleOutside = (e) => {
  if (!e.target.closest('.qe-emoji-zone')) showEmoji.value = false
}

/* ── Quill 마운트 ───────────────────────────────────────── */
onMounted(async () => {
  if (typeof window === 'undefined') return   // SSR 안전

  await import('quill/dist/quill.snow.css')
  const { default: Quill } = await import('quill')

  quill = new Quill(editorRef.value, {
    theme:       'snow',
    placeholder: props.placeholder,
    modules: {
      toolbar: {
        container: toolbarRef.value,
        handlers: {
          image: handleImageUpload,
          video: openVideoModal,
        },
      },
    },
  })

  // 초기 값 세팅
  if (props.modelValue) {
    quill.root.innerHTML = props.modelValue
  }

  // 에디터 변경 → 부모로 emit
  quill.on('text-change', () => {
    isInternal = true
    const html = quill.root.innerHTML
    emit('update:modelValue', html === '<p><br></p>' ? '' : html)
    nextTick(() => { isInternal = false })
  })

  document.addEventListener('click', handleOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutside)
  quill = null
})

/* ── 외부에서 v-model 값 변경 시 동기화 ─────────────────── */
watch(() => props.modelValue, (val) => {
  if (!quill || isInternal) return
  if (val !== quill.root.innerHTML) {
    quill.root.innerHTML = val ?? ''
  }
})
</script>

<template>
  <div class="quill-wrapper rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 overflow-visible">

    <!-- ── 툴바 ──────────────────────────────────────────── -->
    <div ref="toolbarRef" class="qe-toolbar bg-gray-50 dark:bg-slate-800/80 border-b border-gray-200 dark:border-slate-700 rounded-t-xl px-2 py-1.5 flex flex-wrap items-center gap-0.5">

      <!-- 텍스트 스타일 -->
      <button class="ql-bold"       title="굵게 (Ctrl+B)"></button>
      <button class="ql-italic"     title="기울임 (Ctrl+I)"></button>
      <button class="ql-underline"  title="밑줄 (Ctrl+U)"></button>
      <button class="ql-strike"     title="취소선"></button>

      <span class="qe-sep"></span>

      <!-- 제목 크기 -->
      <select class="ql-header" title="제목 크기">
        <option value="1">H1</option>
        <option value="2">H2</option>
        <option value="3">H3</option>
        <option value="">본문</option>
      </select>

      <span class="qe-sep"></span>

      <!-- 목록 / 인용 / 코드 -->
      <button class="ql-list" value="ordered" title="번호 목록"></button>
      <button class="ql-list" value="bullet"  title="점 목록"></button>
      <button class="ql-blockquote"            title="인용"></button>
      <button class="ql-code-block"            title="코드 블록"></button>

      <span class="qe-sep"></span>

      <!-- 색상 -->
      <select class="ql-color"      title="글자색"></select>
      <select class="ql-background" title="배경 강조색"></select>

      <span class="qe-sep"></span>

      <!-- 정렬 -->
      <button class="ql-align" value=""        title="왼쪽 정렬"></button>
      <button class="ql-align" value="center"  title="가운데 정렬"></button>
      <button class="ql-align" value="right"   title="오른쪽 정렬"></button>

      <span class="qe-sep"></span>

      <!-- 링크 / 이미지 / 동영상 -->
      <button class="ql-link"  title="링크 삽입"></button>
      <button class="ql-image" title="이미지 업로드 (최대 5MB)"></button>
      <button class="ql-video" title="YouTube / Vimeo 동영상 삽입"></button>

      <span class="qe-sep"></span>

      <!-- 이모지 버튼 (커스텀) -->
      <div class="qe-emoji-zone relative">
        <button
          type="button"
          @click.stop="showEmoji = !showEmoji"
          class="qe-icon-btn"
          title="이모지"
        >😊</button>

        <!-- 이모지 피커 -->
        <div
          v-if="showEmoji"
          class="absolute top-full left-0 mt-1 z-50 bg-slate-800 border border-slate-600 rounded-2xl p-3 shadow-2xl"
          style="width: 268px;"
        >
          <div v-for="(row, ri) in emojiRows" :key="ri" class="flex gap-0.5 mb-0.5">
            <button
              v-for="emoji in row"
              :key="emoji"
              type="button"
              @click="insertEmoji(emoji)"
              class="w-8 h-8 flex items-center justify-center text-lg hover:bg-slate-700 rounded-lg transition-colors"
            >{{ emoji }}</button>
          </div>
        </div>
      </div>

      <!-- 업로드 중 인디케이터 -->
      <span v-if="uploading" class="ml-2 text-xs text-violet-400 animate-pulse flex items-center gap-1">
        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        업로드 중...
      </span>
    </div>

    <!-- ── 에디터 본문 ─────────────────────────────────── -->
    <div ref="editorRef" :style="{ minHeight: minHeight }"></div>

    <!-- ── 동영상 삽입 모달 ───────────────────────────── -->
    <Teleport to="body">
      <div v-if="showVideo" class="fixed inset-0 z-[9999] flex items-center justify-center px-4">
        <!-- 백드롭 -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showVideo = false"/>

        <!-- 모달 본체 -->
        <div class="relative bg-slate-800 border border-slate-600 rounded-2xl p-6 w-full max-w-lg shadow-2xl">
          <h3 class="text-white font-black text-lg mb-1">🎬 동영상 삽입</h3>
          <p class="text-slate-400 text-xs mb-4">
            YouTube · Vimeo URL을 입력하면 자동으로 임베드됩니다.
            직접 영상 파일 URL 입력도 지원합니다.
          </p>

          <!-- URL 입력 -->
          <input
            id="qe-video-input"
            v-model="videoInput"
            type="url"
            placeholder="https://www.youtube.com/watch?v=..."
            class="w-full bg-slate-900 border border-slate-700 focus:border-violet-500 rounded-xl px-4 py-3 text-white text-sm placeholder-slate-500 outline-none transition-colors"
            @keydown.enter="embedVideo"
          />

          <!-- 지원 포맷 안내 -->
          <div class="mt-3 flex flex-wrap gap-2">
            <span class="text-xs bg-red-500/15 text-red-400 border border-red-500/30 px-2 py-0.5 rounded-full">YouTube</span>
            <span class="text-xs bg-blue-500/15 text-blue-400 border border-blue-500/30 px-2 py-0.5 rounded-full">Vimeo</span>
            <span class="text-xs bg-slate-700 text-slate-400 border border-slate-600 px-2 py-0.5 rounded-full">직접 URL</span>
          </div>

          <!-- 영상 인코딩 안내 -->
          <div class="mt-3 bg-slate-900/60 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-slate-500">
            💡 직접 영상 파일 업로드(인코딩)는 추후 업데이트 예정입니다.
          </div>

          <div class="flex justify-end gap-3 mt-5">
            <button @click="showVideo = false" type="button"
              class="px-5 py-2.5 rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-300 text-sm font-medium transition-colors">
              취소
            </button>
            <button @click="embedVideo" type="button"
              :disabled="!videoInput.trim()"
              class="px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-sm transition-colors">
              삽입
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
/* ── 툴바 구분선 ──────────────────────────────────── */
.qe-sep {
  display: inline-block;
  width: 1px;
  height: 20px;
  background: #e2e8f0;
  margin: 0 4px;
  align-self: center;
  flex-shrink: 0;
}
:global(html.dark) .qe-sep { background: #334155; }

/* ── 이모지 커스텀 버튼 ───────────────────────────── */
.qe-icon-btn {
  width: 28px;
  height: 28px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: background 0.15s;
  background: transparent;
  border: none;
  cursor: pointer;
}
.qe-icon-btn:hover { background: #f1f5f9; }
:global(html.dark) .qe-icon-btn:hover { background: #334155; }

/* ── Quill 공통 ──────────────────────────────────── */
:deep(.ql-toolbar.ql-snow) {
  border: none !important;
  padding: 0 !important;
  background: transparent;
  font-family: 'Noto Sans KR', sans-serif;
}
:deep(.ql-container.ql-snow) {
  border: none !important;
  font-size: 15px;
  font-family: 'Noto Sans KR', sans-serif;
  color: #1e293b;
}
:deep(.ql-editor) {
  min-height: v-bind(minHeight);
  padding: 18px 20px;
  line-height: 1.8;
  color: #1e293b;
}
:deep(.ql-editor.ql-blank::before) {
  color: #94a3b8;
  font-style: normal;
  left: 20px;
}

/* ── 다크 모드 텍스트 ──────────────────────────────── */
:global(html.dark) :deep(.ql-container.ql-snow) { color: #cbd5e1; }
:global(html.dark) :deep(.ql-editor)             { color: #cbd5e1; }
:global(html.dark) :deep(.ql-editor.ql-blank::before) { color: #475569; }

/* ── 툴바 아이콘 (라이트) ─────────────────────────── */
:deep(.ql-snow .ql-stroke) { stroke: #64748b; }
:deep(.ql-snow .ql-fill)   { fill:   #64748b; }
:deep(.ql-snow .ql-picker)  { color:  #64748b; }
:deep(.ql-snow .ql-picker-label)         { color: #64748b; }
:deep(.ql-snow .ql-picker-label::before) { color: #64748b; }

/* 툴바 아이콘 (다크) */
:global(html.dark) :deep(.ql-snow .ql-stroke) { stroke: #94a3b8; }
:global(html.dark) :deep(.ql-snow .ql-fill)   { fill:   #94a3b8; }
:global(html.dark) :deep(.ql-snow .ql-picker)  { color:  #94a3b8; }
:global(html.dark) :deep(.ql-snow .ql-picker-label)         { color: #94a3b8; }
:global(html.dark) :deep(.ql-snow .ql-picker-label::before) { color: #94a3b8; }

/* 드롭다운 옵션 (라이트) */
:deep(.ql-snow .ql-picker-options) {
  background: #ffffff;
  border-color: #e2e8f0;
  border-radius: 8px;
  padding: 4px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
:deep(.ql-snow .ql-picker-item) { color: #334155; }
:deep(.ql-snow .ql-picker-item:hover) { color: #7c3aed; }

/* 드롭다운 옵션 (다크) */
:global(html.dark) :deep(.ql-snow .ql-picker-options) {
  background: #1e293b;
  border-color: #334155;
  box-shadow: 0 4px 12px rgba(0,0,0,0.4);
}
:global(html.dark) :deep(.ql-snow .ql-picker-item) { color: #cbd5e1; }
:global(html.dark) :deep(.ql-snow .ql-picker-item:hover) { color: #a78bfa; }

/* 활성/호버 (라이트) */
:deep(.ql-snow button:hover .ql-stroke),
:deep(.ql-snow button.ql-active .ql-stroke),
:deep(.ql-snow .ql-picker-label:hover .ql-stroke) { stroke: #7c3aed; }
:deep(.ql-snow button:hover .ql-fill),
:deep(.ql-snow button.ql-active .ql-fill)         { fill: #7c3aed; }
:deep(.ql-snow button:hover),
:deep(.ql-snow button.ql-active) { background: #f3f4f6; border-radius: 4px; }

/* 활성/호버 (다크) */
:global(html.dark) :deep(.ql-snow button:hover .ql-stroke),
:global(html.dark) :deep(.ql-snow button.ql-active .ql-stroke),
:global(html.dark) :deep(.ql-snow .ql-picker-label:hover .ql-stroke) { stroke: #a78bfa; }
:global(html.dark) :deep(.ql-snow button:hover .ql-fill),
:global(html.dark) :deep(.ql-snow button.ql-active .ql-fill)         { fill: #a78bfa; }
:global(html.dark) :deep(.ql-snow button:hover),
:global(html.dark) :deep(.ql-snow button.ql-active) { background: #1e293b; border-radius: 4px; }

/* 헤더 선택자 레이블 */
:deep(.ql-snow .ql-picker.ql-header .ql-picker-label[data-value='1']::before) { content: 'H1'; }
:deep(.ql-snow .ql-picker.ql-header .ql-picker-label[data-value='2']::before) { content: 'H2'; }
:deep(.ql-snow .ql-picker.ql-header .ql-picker-label[data-value='3']::before) { content: 'H3'; }
:deep(.ql-snow .ql-picker.ql-header .ql-picker-label:not([data-value])::before) { content: '본문'; }
:deep(.ql-snow .ql-picker.ql-header .ql-picker-item[data-value='1']::before) { content: '제목 1'; }
:deep(.ql-snow .ql-picker.ql-header .ql-picker-item[data-value='2']::before) { content: '제목 2'; }
:deep(.ql-snow .ql-picker.ql-header .ql-picker-item[data-value='3']::before) { content: '제목 3'; }
:deep(.ql-snow .ql-picker.ql-header .ql-picker-item:not([data-value])::before) { content: '본문'; }

/* ── 에디터 콘텐츠 (라이트) ──────────────────────── */
:deep(.ql-editor h1) { font-size: 1.75rem; font-weight: 900; color: #111827; margin: 1rem 0 0.5rem; }
:deep(.ql-editor h2) { font-size: 1.375rem; font-weight: 800; color: #1f2937; margin: 0.875rem 0 0.4rem; }
:deep(.ql-editor h3) { font-size: 1.125rem; font-weight: 700; color: #374151; margin: 0.75rem 0 0.35rem; }
:deep(.ql-editor blockquote) {
  border-left: 4px solid #7c3aed;
  margin: 12px 0; padding: 10px 16px;
  background: #f5f3ff;
  border-radius: 0 8px 8px 0;
  color: #6d28d9;
}
:deep(.ql-editor pre.ql-syntax) {
  background: #f8fafc; border: 1px solid #e2e8f0;
  border-radius: 10px; padding: 14px 18px;
  color: #0f172a; font-family: 'JetBrains Mono','Fira Code',monospace;
  font-size: 0.875rem; overflow-x: auto;
}
:deep(.ql-editor a) { color: #7c3aed; text-decoration: underline; }

/* 에디터 콘텐츠 (다크) */
:global(html.dark) :deep(.ql-editor h1) { color: #f1f5f9; }
:global(html.dark) :deep(.ql-editor h2) { color: #f1f5f9; }
:global(html.dark) :deep(.ql-editor h3) { color: #e2e8f0; }
:global(html.dark) :deep(.ql-editor blockquote) {
  background: #1e1b4b40; color: #a5b4fc; border-left-color: #6d28d9;
}
:global(html.dark) :deep(.ql-editor pre.ql-syntax) {
  background: #0f172a; border-color: #334155; color: #7dd3fc;
}
:global(html.dark) :deep(.ql-editor a) { color: #818cf8; }

/* 공통 */
:deep(.ql-editor img)      { max-width: 100%; border-radius: 10px; margin: 8px 0; display: block; }
:deep(.ql-editor .ql-video){ display: block; width: 100%; aspect-ratio: 16/9; border-radius: 10px; margin: 10px 0; border: none; }
:deep(.ql-editor ul),
:deep(.ql-editor ol)       { padding-left: 1.5em; }
:deep(.ql-editor li)       { margin: 4px 0; }

/* ── 링크 tooltip (라이트) ────────────────────────── */
:deep(.ql-snow .ql-tooltip) {
  background: #ffffff; border-color: #e2e8f0;
  border-radius: 10px; color: #334155;
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}
:deep(.ql-snow .ql-tooltip input[type='text']) {
  background: #f8fafc; border-color: #cbd5e1;
  border-radius: 6px; color: #0f172a; outline: none;
}
:deep(.ql-snow .ql-tooltip a.ql-action),
:deep(.ql-snow .ql-tooltip a.ql-remove) { color: #7c3aed; }

/* 링크 tooltip (다크) */
:global(html.dark) :deep(.ql-snow .ql-tooltip) {
  background: #1e293b; border-color: #334155; color: #cbd5e1;
  box-shadow: 0 8px 24px rgba(0,0,0,0.5);
}
:global(html.dark) :deep(.ql-snow .ql-tooltip input[type='text']) {
  background: #0f172a; border-color: #475569; color: #f1f5f9;
}
:global(html.dark) :deep(.ql-snow .ql-tooltip a.ql-action),
:global(html.dark) :deep(.ql-snow .ql-tooltip a.ql-remove) { color: #a78bfa; }
</style>
