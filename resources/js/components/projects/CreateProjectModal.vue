<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/ProjectController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Form } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const isOpen = defineModel<boolean>('isOpen');
const emit = defineEmits<{
    success: [];
}>();

const emails = ref<string[]>(['']);

const addEmail = () => {
    emails.value.push('');
};

const removeEmail = (index: number) => {
    emails.value.splice(index, 1);
    if (emails.value.length === 0) {
        emails.value.push('');
    }
};

const handleSuccess = () => {
    emit('success');
    resetForm();
};

const resetForm = () => {
    emails.value = [''];
};

watch(
    () => isOpen.value,
    (open) => {
        if (!open) {
            resetForm();
        }
    },
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Add New Project</DialogTitle>
                <DialogDescription>
                    Create a new project to monitor. Add notification emails to
                    receive alerts when health checks fail.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="store.form()"
                class="space-y-6"
                v-slot="{ errors, processing, recentlySuccessful }"
                @success="handleSuccess"
            >
                <div class="grid gap-2">
                    <Label for="name">Project Name</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        placeholder="My API"
                        autocomplete="off"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="health_check_url">Health Check URL</Label>
                    <Input
                        id="health_check_url"
                        name="health_check_url"
                        type="url"
                        required
                        placeholder="https://api.example.com/health"
                        autocomplete="off"
                    />
                    <InputError :message="errors.health_check_url" />
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox
                        id="is_active"
                        name="is_active"
                        value="1"
                        default-checked
                    />
                    <Label for="is_active" class="cursor-pointer">
                        Active (start monitoring immediately)
                    </Label>
                    <InputError :message="errors.is_active" />
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <Label>Notification Emails (optional)</Label>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="addEmail"
                        >
                            <Plus class="mr-1 size-4" />
                            Add Email
                        </Button>
                    </div>

                    <div
                        v-for="(email, index) in emails"
                        :key="index"
                        class="flex gap-2"
                    >
                        <Input
                            :name="`notification_emails[${index}]`"
                            type="email"
                            :value="email"
                            @input="
                                emails[index] = (
                                    $event.target as HTMLInputElement
                                ).value
                            "
                            placeholder="admin@example.com"
                            class="flex-1"
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="removeEmail(index)"
                            :disabled="emails.length === 1"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                    <InputError :message="errors['notification_emails.0']" />
                </div>

                <div class="flex items-center justify-end gap-3 border-t pt-4">
                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-if="recentlySuccessful"
                            class="text-sm text-muted-foreground"
                        >
                            Saved.
                        </p>
                    </Transition>

                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                        :disabled="processing"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Create Project
                    </Button>
                </div>
            </Form>
        </DialogContent>
    </Dialog>
</template>
