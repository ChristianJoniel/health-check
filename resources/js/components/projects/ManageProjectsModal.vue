<script setup lang="ts">
import {
    destroy,
    index,
} from '@/actions/App/Http/Controllers/ProjectController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { router } from '@inertiajs/vue3';
import { Mail, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import CreateProjectModal from './CreateProjectModal.vue';
import EditProjectModal from './EditProjectModal.vue';
import ManageEmailsModal from './ManageEmailsModal.vue';
import ProjectStatusBadge from './ProjectStatusBadge.vue';

interface ProjectResource {
    id: number;
    name: string;
    health_check_url: string;
    is_active: boolean;
    notification_emails_count: number;
    created_at: string;
    updated_at: string;
}

const isOpen = defineModel<boolean>('isOpen');

const projects = ref<ProjectResource[]>([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showEmailsModal = ref(false);
const selectedProject = ref<ProjectResource | null>(null);
const deleting = ref<number | null>(null);

const fetchProjects = async () => {
    loading.value = true;
    try {
        const response = await fetch(index.url(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();
        projects.value = data.props.projects.data || data.props.projects;
    } catch (error) {
        console.error('Failed to fetch projects:', error);
    } finally {
        loading.value = false;
    }
};

const handleEdit = (project: ProjectResource) => {
    selectedProject.value = project;
    showEditModal.value = true;
};

const handleManageEmails = (project: ProjectResource) => {
    selectedProject.value = project;
    showEmailsModal.value = true;
};

const handleDelete = (project: ProjectResource) => {
    if (!confirm(`Are you sure you want to delete "${project.name}"?`)) {
        return;
    }

    deleting.value = project.id;
    router.delete(destroy.url(project.id), {
        preserveScroll: true,
        onSuccess: () => {
            fetchProjects();
        },
        onFinish: () => {
            deleting.value = null;
        },
    });
};

const handleModalSuccess = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    showEmailsModal.value = false;
    selectedProject.value = null;
    fetchProjects();
};

watch(
    () => isOpen.value,
    (open) => {
        if (open) {
            fetchProjects();
        }
    },
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent
            class="flex max-h-[80vh] max-w-4xl flex-col overflow-hidden"
        >
            <DialogHeader>
                <DialogTitle>Manage Projects</DialogTitle>
                <DialogDescription>
                    Create, edit, and manage your monitored projects and their
                    notification settings.
                </DialogDescription>
            </DialogHeader>

            <div class="mb-4 flex justify-end">
                <Button @click="showCreateModal = true">
                    <Plus class="mr-2 size-4" />
                    Add Project
                </Button>
            </div>

            <div v-if="loading" class="flex items-center justify-center py-12">
                <Spinner class="size-8" />
            </div>

            <div
                v-else-if="projects.length === 0"
                class="py-12 text-center text-muted-foreground"
            >
                <p>
                    No projects found. Create your first project to get started.
                </p>
            </div>

            <div v-else class="flex-1 overflow-auto">
                <table class="w-full">
                    <thead class="border-b">
                        <tr class="text-left">
                            <th class="pr-4 pb-3 text-sm font-medium">Name</th>
                            <th class="pr-4 pb-3 text-sm font-medium">URL</th>
                            <th class="pr-4 pb-3 text-sm font-medium">
                                Status
                            </th>
                            <th class="pr-4 pb-3 text-sm font-medium">
                                Emails
                            </th>
                            <th class="pb-3 text-right text-sm font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="project in projects"
                            :key="project.id"
                            class="border-b last:border-0"
                        >
                            <td class="py-3 pr-4">{{ project.name }}</td>
                            <td class="py-3 pr-4 text-sm text-muted-foreground">
                                {{ project.health_check_url }}
                            </td>
                            <td class="py-3 pr-4">
                                <ProjectStatusBadge
                                    :is-active="project.is_active"
                                />
                            </td>
                            <td class="py-3 pr-4 text-sm">
                                {{ project.notification_emails_count }}
                            </td>
                            <td class="py-3">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="handleEdit(project)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="handleManageEmails(project)"
                                    >
                                        <Mail class="size-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        :disabled="deleting === project.id"
                                        @click="handleDelete(project)"
                                    >
                                        <Spinner
                                            v-if="deleting === project.id"
                                            class="size-4"
                                        />
                                        <Trash2 v-else class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </DialogContent>
    </Dialog>

    <CreateProjectModal
        v-model:is-open="showCreateModal"
        @success="handleModalSuccess"
    />

    <EditProjectModal
        v-if="selectedProject"
        v-model:is-open="showEditModal"
        :project="selectedProject"
        @success="handleModalSuccess"
    />

    <ManageEmailsModal
        v-if="selectedProject"
        v-model:is-open="showEmailsModal"
        :project="selectedProject"
        @success="handleModalSuccess"
    />
</template>
