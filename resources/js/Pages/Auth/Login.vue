<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { FwbTable, FwbTableBody, FwbTableCell, FwbTableHead, FwbTableHeadCell, FwbTableRow, FwbButton } from 'flowbite-vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const users = ref([]);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const copyUser = (user) => {
    form.email = user.email;
    form.password = user.password;
};

const loadUsers = async () => {
    try {
        const response = await axios.get('/api/users');
        users.value = response.data.data;
    } catch (error) {
        console.error('Ошибка загрузки пользователей:', error);
    }
};

const generateNew = async () => {
    try {
        const response = await axios.get('/api/users/generate');
        users.value = response.data.data;
    } catch (error) {
        console.error('Ошибка генерации пользователей:', error);
    }
};


onMounted(loadUsers);
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="bg-white p-6 rounded-lg shadow-md mb-12 w-full max-w-md mx-auto">
            <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                {{ status }}
            </div>

            <form @submit.prevent="submit">
                <div>
                    <InputLabel for="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="mt-4">
                    <InputLabel for="password" value="Password" />

                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="mt-4 flex items-center justify-end">

                    <PrimaryButton
                        class="ms-4"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Log in
                    </PrimaryButton>
                </div>
            </form>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md w-full md:w-3/5 h-[50vh] mx-auto overflow-auto flex flex-col">
            <FwbTable striped hoverable class="flex-grow">
                <FwbTableHead>
                    <FwbTableHeadCell>Email</FwbTableHeadCell>
                    <FwbTableHeadCell>Password</FwbTableHeadCell>
                    <FwbTableHeadCell>Дата создания</FwbTableHeadCell>
                    <FwbTableHeadCell>Скопировать</FwbTableHeadCell>
                </FwbTableHead>
                <FwbTableBody>
                    <FwbTableRow v-for="user in users" :key="user.id">
                        <FwbTableCell>{{ user.email }}</FwbTableCell>
                        <FwbTableCell>{{ user.password }}</FwbTableCell>
                        <FwbTableCell>{{ user.created_at }}</FwbTableCell>
                        <FwbTableCell>
                            <FwbButton size="sm" variant="green" class="bg-green-500 text-white" @click="copyUser(user)">Скопировать</FwbButton>
                        </FwbTableCell>
                    </FwbTableRow>
                </FwbTableBody>
            </FwbTable>

            <div class="flex justify-center space-x-4 mt-4">
                <FwbButton variant="green" class="bg-green-500 text-white" @click.prevent="generateNew">Сгенерировать новых пользователей</FwbButton>
            </div>
        </div>
    </GuestLayout>
</template>
