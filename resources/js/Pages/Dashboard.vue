<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { FwbFileInput, FwbToggle, FwbButton } from 'flowbite-vue';

const file = ref(null);
const toggle = ref(false);

const form = useForm({
    file: null,
    is_private: false,
});

const submit = () => {
    form.file = file.value;
    form.is_private = toggle.value;

    form.post(route('upload'), {
        forceFormData: true,
        onSuccess: () => {
            file.value = null;
            toggle.value = false;
            alert('Файл успешно загружен!');
        },
        onError: (errors) => {
            console.error(errors);
        },
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 px-10">
                <fwb-file-input
                    v-model="file"
                    label="Загрузить файл"
                    helper="Только CSV"
                    accept=".csv"
                    dropzone
                />

                <div class="mt-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <fwb-toggle v-model="toggle" />
                            <p class="ml-4 text-sm font-medium text-gray-700">
                                Тип: {{ toggle ? 'Приватный' : 'Публичный' }}
                            </p>
                        </div>
                        <fwb-button
                            size="md"
                            color="green"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            Загрузить
                        </fwb-button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
