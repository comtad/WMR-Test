<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
    FwbTable,
    FwbTableBody,
    FwbTableCell,
    FwbTableHead,
    FwbTableHeadCell,
    FwbTableRow,
    FwbButton
} from 'flowbite-vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useClipboard } from '@vueuse/core';

defineProps({
    status: {
        type: String,
        default: ''
    },
});

// Состояния загрузки
const isLoading = ref(false);
const isGenerating = ref(false);
const { copy, isSupported } = useClipboard();

const users = ref([]);
const copiedUser = ref(null);

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const copyUser = async (user) => {
    if (isSupported) {
        await copy(`${user.email}:${user.password}`);
        copiedUser.value = user.id;

        // Сбрасываем подсветку через 2 секунды
        setTimeout(() => {
            copiedUser.value = null;
        }, 2000);
    }

    form.email = user.email;
    form.password = user.password;
};

const loadUsers = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/users');
        users.value = response.data.data;
    } catch (error) {
        console.error('Ошибка загрузки пользователей:', error);
    } finally {
        isLoading.value = false;
    }
};

const generateNew = async () => {
    isGenerating.value = true;
    try {
        const response = await axios.get('/api/users/generate');
        users.value = response.data.data;
    } catch (error) {
        console.error('Ошибка генерации пользователей:', error);
    } finally {
        isGenerating.value = false;
    }
};

onMounted(loadUsers);
</script>

<template>
    <GuestLayout>
        <Head title="Вход в систему" />

        <div class="flex flex-col md:flex-row gap-6 max-w-6xl mx-auto p-4">
            <!-- Форма входа -->
            <div class="bg-white p-6 rounded-xl shadow-lg w-full md:w-2/5">
                <div class="text-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Добро пожаловать</h1>
                    <p class="text-gray-600 mt-2">Введите свои учетные данные</p>
                </div>

                <div v-if="status" class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-center">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel for="email" value="Электронная почта" class="mb-1" />
                        <TextInput
                            id="email"
                            type="email"
                            class="w-full py-2.5 px-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="user@example.com"
                        />
                        <InputError class="mt-1.5" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Пароль" />

                        <TextInput
                            id="password"
                            type="password"
                            class="w-full py-2.5 px-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <InputError class="mt-1.5" :message="form.errors.password" />
                    </div>

                    <div class="flex justify-end">
                        <PrimaryButton
                            class="px-6 py-2.5 font-medium"
                            :class="{ 'opacity-50': form.processing }"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Вход...</span>
                            <span v-else>Войти</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Панель с тестовыми пользователями -->
            <div class="bg-white p-6 rounded-xl shadow-lg w-full md:w-3/5 flex flex-col">
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        Тестовые пользователи
                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                            Демо-режим
                        </span>
                    </h2>
                    <p class="text-gray-600 mt-1 text-sm">
                        Нажмите "Скопировать" для автоматического заполнения данных
                    </p>
                </div>

                <div class="relative flex-grow min-h-[300px]">
                    <FwbTable striped hoverable class="w-full">
                        <FwbTableHead class="sticky top-0 bg-white">
                            <FwbTableHeadCell>Email</FwbTableHeadCell>
                            <FwbTableHeadCell>Дата создания</FwbTableHeadCell>
                            <FwbTableHeadCell class="w-32">Действия</FwbTableHeadCell>
                        </FwbTableHead>

                        <FwbTableBody v-if="!isLoading">
                            <FwbTableRow
                                v-for="user in users"
                                :key="user.id"
                                :class="{ 'bg-blue-50': copiedUser === user.id }"
                                class="transition-colors"
                            >
                                <FwbTableCell class="font-medium truncate">{{ user.email }}</FwbTableCell>
                                <FwbTableCell class="text-gray-500">
                                    {{ user.created_at }}
                                </FwbTableCell>
                                <FwbTableCell>
                                    <FwbButton
                                        size="xs"
                                        :color="copiedUser === user.id ? 'green' : 'default'"
                                        class="w-full justify-center"
                                        @click="copyUser(user)"
                                    >
                                        <span v-if="copiedUser === user.id">Скопировано!</span>
                                        <span v-else>Скопировать</span>
                                    </FwbButton>
                                </FwbTableCell>
                            </FwbTableRow>
                        </FwbTableBody>
                    </FwbTable>

                    <!-- Состояние загрузки -->
                    <div v-if="isLoading" class="absolute inset-0 bg-white bg-opacity-80 flex items-center justify-center">
                        <p class="text-gray-600">Загрузка данных...</p>
                    </div>

                    <!-- Пустое состояние -->
                    <div v-if="!isLoading && users.length === 0" class="text-center py-10">
                        <svg class="w-16 h-16 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <p class="mt-4 text-gray-500">Нет тестовых пользователей</p>
                    </div>
                </div>

                <div class="flex justify-center mt-6">
                    <FwbButton
                        color="green"
                        class="px-5 py-2.5 font-medium"
                        @click="generateNew"
                        :disabled="isGenerating"
                        :class="{ 'opacity-70': isGenerating }"
                    >
                        <span>{{ isGenerating ? 'Генерация...' : 'Сгенерировать новых пользователей' }}</span>
                    </FwbButton>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
