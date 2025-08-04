<template>
    <div>
        <div
            class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer transition-colors hover:border-blue-500 min-h-[130px] flex items-center justify-center"
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="handleDrop"
            @click="triggerFileInput"
        >
            <input
                ref="fileInput"
                type="file"
                class="hidden"
                accept=".csv"
                @change="handleFileChange"
            >
            <div class="flex flex-col items-center justify-center">
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <p class="font-medium text-gray-700">Загрузить файл</p>
                <p class="text-sm text-gray-500 mt-1">Нажмите или перетащите CSV файл сюда</p>
            </div>
        </div>

        <div v-if="file" class="mt-3 p-3 bg-gray-50 rounded-lg flex items-center">
            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span class="text-sm text-gray-700 truncate">{{ file.name }}</span>
            <button
                class="ml-auto text-gray-500 hover:text-red-500"
                @click="removeFile"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Имя файла</label>
            <input
                v-model="fileName"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                placeholder="Введите имя для файла"
                @input="updateFileName"
            >
        </div>

        <div class="mt-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input
                            v-model="toggle"
                            type="checkbox"
                            class="sr-only peer"
                        >
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-700">
                            Тип: {{ toggle ? 'Приватный' : 'Публичный' }}
                        </span>
                    </label>
                </div>
                <button
                    :disabled="loading || !file"
                    :class="[
                        'px-4 py-2 rounded-md font-medium transition-colors',
                        (loading || !file)
                            ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                            : 'bg-green-600 text-white hover:bg-green-700'
                    ]"
                    @click="submit"
                >
                    {{ loading ? 'Загрузка...' : 'Загрузить' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const emit = defineEmits(['uploaded']);

const file = ref(null);
const fileName = ref('');
const toggle = ref(false);
const loading = ref(false);
const dragOver = ref(false);
const fileInput = ref(null);

const updateFileName = () => {
    if (file.value) {
        const extension = file.value.name.split('.').pop();
        const newFileName = fileName.value.endsWith(`.${extension}`)
            ? fileName.value
            : `${fileName.value}.${extension}`;

        file.value = new File([file.value], newFileName, {
            type: file.value.type,
            lastModified: file.value.lastModified
        });
    }
};

const handleDrop = (e) => {
    dragOver.value = false;
    const droppedFile = e.dataTransfer.files[0];
    if (droppedFile && droppedFile.name.endsWith('.csv')) {
        file.value = droppedFile;
        fileName.value = droppedFile.name.replace(/\.[^/.]+$/, ""); // Удаляем расширение
    }
};

const handleFileChange = (e) => {
    const selectedFile = e.target.files[0];
    if (selectedFile && selectedFile.name.endsWith('.csv')) {
        file.value = selectedFile;
        fileName.value = selectedFile.name.replace(/\.[^/.]+$/, ""); // Удаляем расширение
    }
};

const triggerFileInput = () => {
    fileInput.value.click();
};

const removeFile = () => {
    file.value = null;
    fileName.value = '';
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const getCsrfToken = async () => {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
};

const submit = async () => {
    if (!file.value) {
        return;
    }

    loading.value = true;
    await getCsrfToken();

    const formData = new FormData();
    formData.append('file', file.value);
    formData.append('is_private', toggle.value ? '1' : '0');

    try {
        const response = await axios.post('/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            withCredentials: true,
        });

        file.value = null;
        fileName.value = '';
        toggle.value = false;
        if (fileInput.value) fileInput.value.value = '';

        emit('uploaded', response.data);
    } catch (error) {
        const errorMessage = error.response?.data?.message ||
            error.message ||
            'Неизвестная ошибка при загрузке файла';
        console.error('Ошибка загрузки:', error);
    } finally {
        loading.value = false;
    }
};
</script>
