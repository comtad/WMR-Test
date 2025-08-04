<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import {
    FwbTable,
    FwbTableHead,
    FwbTableHeadCell,
    FwbTableBody,
    FwbTableRow,
    FwbTableCell,
    FwbPagination
} from 'flowbite-vue';
import FileUploader from '@/Components/FileUploader.vue';
import axios from 'axios';

const mediaItems = ref([]);
const currentPage = ref(1);
const totalPages = ref(1);
const isFetching = ref(false);

const { props } = usePage();
const userId = props.auth.user.id;

const fetchMedia = async (page = 1) => {
    isFetching.value = true;
    try {
        const response = await axios.get('/media/accessible', {
            params: { page: page }
        });

        // Плавное обновление данных
        mediaItems.value = [...response.data.data];
        currentPage.value = response.data.meta.current_page;
        totalPages.value = response.data.meta.last_page;
    } catch (error) {
        console.error('Ошибка загрузки медиа:', error);
        mediaItems.value = [];
    } finally {
        isFetching.value = false;
    }
};

const handlePageChange = (page) => {
    fetchMedia(page);
};

const handleUploaded = () => {
    fetchMedia(currentPage.value);
};

const handleMediaEvent = (event) => {
    if (event.action === 'update_table') {
        fetchMedia(currentPage.value);
    }
};

let mediaChannel = null;

const connectWebSocket = () => {
    mediaChannel = window.Echo.channel('media.events');
    mediaChannel.listen('.media.media', handleMediaEvent);
};

const disconnectWebSocket = () => {
    if (mediaChannel) {
        mediaChannel.stopListening('.media.media', handleMediaEvent);
    }
};

onMounted(() => {
    fetchMedia();
    connectWebSocket();
});

onBeforeUnmount(() => {
    disconnectWebSocket();
});
</script>

<template>
    <Head title="Загрузчик файлов" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Загрузчик файлов
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 px-10">
                <!-- Компонент загрузки файла -->
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-4">Загрузка CSV файлов</h3>
                    <FileUploader @uploaded="handleUploaded" />
                </div>

                <!-- Таблица с историей загрузок -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">История загрузок</h3>
                        <!-- Только отображение текущей страницы -->
                        <div v-if="totalPages > 1" class="flex items-center">
                            <span class="text-sm text-gray-600">
                                Страница {{ currentPage }} из {{ totalPages }}
                            </span>
                        </div>
                    </div>

                    <fwb-table striped>
                        <fwb-table-head>
                            <fwb-table-head-cell>Дата</fwb-table-head-cell>
                            <fwb-table-head-cell>Имя файла</fwb-table-head-cell>
                            <fwb-table-head-cell>Записей</fwb-table-head-cell>
                            <fwb-table-head-cell>Статус</fwb-table-head-cell>
                            <fwb-table-head-cell>Автор</fwb-table-head-cell>
                            <fwb-table-head-cell class="text-right">Действия</fwb-table-head-cell>
                        </fwb-table-head>
                        <fwb-table-body>
                            <fwb-table-row
                                v-for="item in mediaItems"
                                :key="item.id"
                                :class="{
                                    'opacity-50': isFetching,
                                    'transition-opacity duration-300': true
                                }"
                            >
                                <fwb-table-cell>{{ item.date }}</fwb-table-cell>
                                <fwb-table-cell>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span class="truncate max-w-xs">{{ item.filename }}</span>
                                    </div>
                                </fwb-table-cell>
                                <fwb-table-cell>{{ item.record_count }}</fwb-table-cell>
                                <fwb-table-cell>
                                    <!-- Обновлённый стиль статуса с рамкой -->
                                    <span
                                        :class="item.status.color"
                                        class="px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                    >
                                        {{ item.status.value }}
                                    </span>
                                </fwb-table-cell>
                                <fwb-table-cell>
                                    <span v-if="item.author">
                                        <svg class="w-4 h-4 text-gray-500 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        {{ item.author }}
                                    </span>
                                    <span v-else class="text-gray-500">
                                        <svg class="w-4 h-4 text-gray-500 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        Приватный
                                    </span>
                                </fwb-table-cell>
                                <fwb-table-cell class="text-right">
                                    <a
                                        v-if="item.download_json && item.download_json !== 'Недоступно'"
                                        :href="item.download_json"
                                        target="_blank"
                                        class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                        <span class="ml-1">Скачать</span>
                                    </a>
                                    <span v-else class="text-gray-400">Недоступно</span>
                                </fwb-table-cell>
                            </fwb-table-row>
                        </fwb-table-body>
                    </fwb-table>

                    <div v-if="mediaItems.length === 0" class="text-center py-8">
                        <svg class="w-16 h-16 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-4 text-gray-500">Нет загруженных файлов</p>
                    </div>

                    <!-- Пагинация со стрелками -->
                    <div v-if="totalPages > 1" class="mt-6 flex justify-end">
                        <fwb-pagination
                            v-model="currentPage"
                            :slice-length="4"
                            :total-pages="totalPages"
                            hide-labels
                            show-icons
                            @update:modelValue="handlePageChange"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
