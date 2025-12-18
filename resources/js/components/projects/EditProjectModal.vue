<script setup lang="ts">
import { update } from '@/actions/App/Http/Controllers/ProjectController';
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

interface ProjectResource {
    id: number;
    name: string;
    health_check_url: string;
    is_active: boolean;
    notification_emails_count: number;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    project: ProjectResource;
}>();

const isOpen = defineModel<boolean>('isOpen');
const emit = defineEmits<{
    success: [];
}>();

const handleSuccess = () => {
    emit('success');
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Edit Project</DialogTitle>
                <DialogDescription>
                    Update project details. Use "Manage Emails" to add or remove
                    notification addresses.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="update.form(project.id)"
                class="space-y-6"
                v-slot="{ errors, processing, recentlySuccessful }"
                @success="handleSuccess"
            >
                <div class="grid gap-2">
                    <Label for="name">Project Name</Label>
                    <Input
                        id="name"
                        name="name"
                        :default-value="project.name"
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
                        :default-value="project.health_check_url"
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
                        :default-checked="project.is_active"
                    />
                    <Label for="is_active" class="cursor-pointer">
                        Active (monitor this project)
                    </Label>
                    <InputError :message="errors.is_active" />
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
                        Save Changes
                    </Button>
                </div>
            </Form>
        </DialogContent>
    </Dialog>
</template>
