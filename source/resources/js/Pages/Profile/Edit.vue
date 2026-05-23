<script setup>
import { computed } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
    errors: { type: Object, default: () => ({}) },
})

const page = usePage()
const user = computed(() => page.props.auth.user)

const form = useForm({
    nickname:             user.value?.nickname ?? '',
    current_password:     '',
    password:             '',
    password_confirmation: '',
})

const submit = () => {
    form.put('/profile', {
        preserveScroll: true,
        onSuccess: () => {
            form.current_password = ''
            form.password = ''
            form.password_confirmation = ''
        },
    })
}

const retakeTest = () => {
    if (confirm('성향 테스트를 다시 응시하면 진영이 변경될 수 있습니다. 계속하시겠습니까?')) {
        window.location.href = '/political-test'
    }
}
</script>

<template>
    <div class="max-w-2xl mx-auto px-4 py-8">
        <!-- 헤더 -->
        <div class="flex items-center gap-3 mb-8">
            <Link href="/profile" class="text-slate-400 hover:text-white transition-colors text-sm">← 내 프로필</Link>
            <span class="text-slate-600">|</span>
            <h1 class="text-xl font-bold text-white">프로필 수정</h1>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- 닉네임 변경 -->
            <div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
                <h2 class="text-white font-semibold mb-4">기본 정보</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-slate-400 mb-1.5">닉네임</label>
                        <input
                            v-model="form.nickname"
                            type="text"
                            class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-violet-500 transition-colors"
                            placeholder="2~20자, 한글/영문/숫자/_"
                        />
                        <p v-if="form.errors.nickname" class="mt-1 text-xs text-red-400">{{ form.errors.nickname }}</p>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-400 mb-1.5">이메일</label>
                        <input
                            :value="user?.email"
                            type="email"
                            disabled
                            class="w-full bg-slate-800/50 border border-slate-700/50 text-slate-500 rounded-lg px-4 py-2.5 text-sm cursor-not-allowed"
                        />
                        <p class="mt-1 text-xs text-slate-500">이메일은 변경할 수 없습니다.</p>
                    </div>
                </div>
            </div>

            <!-- 비밀번호 변경 -->
            <div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
                <h2 class="text-white font-semibold mb-1">비밀번호 변경</h2>
                <p class="text-xs text-slate-500 mb-4">변경하지 않으려면 비워두세요.</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-slate-400 mb-1.5">현재 비밀번호</label>
                        <input
                            v-model="form.current_password"
                            type="password"
                            autocomplete="current-password"
                            class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-violet-500 transition-colors"
                        />
                        <p v-if="form.errors.current_password" class="mt-1 text-xs text-red-400">{{ form.errors.current_password }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1.5">새 비밀번호</label>
                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-violet-500 transition-colors"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-400">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1.5">새 비밀번호 확인</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-violet-500 transition-colors"
                        />
                    </div>
                </div>
            </div>

            <!-- 진영 정보 -->
            <div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
                <h2 class="text-white font-semibold mb-1">나의 정치 성향</h2>
                <p class="text-xs text-slate-500 mb-4">현재 배정된 진영 정보입니다.</p>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">{{ user?.faction_emoji }}</span>
                        <div>
                            <div class="text-white font-semibold">{{ user?.faction_label ?? '미확인' }}</div>
                            <div class="text-xs text-slate-500">정치 성향 테스트 결과</div>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="retakeTest"
                        class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-sm px-4 py-2 rounded-lg transition-colors border border-slate-700"
                    >
                        테스트 재응시
                    </button>
                </div>
            </div>

            <!-- 저장 버튼 -->
            <div class="flex items-center justify-between pt-2">
                <Link href="/profile" class="text-slate-400 hover:text-white text-sm transition-colors">
                    취소
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-violet-600 hover:bg-violet-500 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors"
                >
                    <span v-if="form.processing">저장 중...</span>
                    <span v-else>변경사항 저장</span>
                </button>
            </div>
        </form>
    </div>
</template>
