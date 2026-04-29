<template>
  <AppLayout>
    <div class="p-4 sm:p-6">
      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Access Rules</h2>
          <p class="text-sm text-slate-400">Kelola override rule akses tanpa edit JSON manual.</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
          <button type="button" class="rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-600" @click="resetDraft">Reset Draft</button>
          <button type="button" class="rounded bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500" @click="resetOverrides">Reset Override</button>
          <button type="button" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="saveRules">Simpan Override</button>
        </div>
      </div>

      <div class="mb-4 grid gap-3 lg:grid-cols-3">
        <div class="rounded border border-slate-700 bg-slate-800 p-4">
          <p class="text-xs uppercase tracking-wide text-slate-400">Status Override</p>
          <p class="mt-1 text-sm font-semibold text-white">{{ overrideMeta?.exists ? 'Active' : 'Default Only' }}</p>
        </div>
        <div class="rounded border border-slate-700 bg-slate-800 p-4">
          <p class="text-xs uppercase tracking-wide text-slate-400">Lokasi File</p>
          <p class="mt-1 break-all text-sm text-slate-200">{{ overrideMeta?.path || '-' }}</p>
        </div>
        <div class="rounded border border-slate-700 bg-slate-800 p-4">
          <p class="text-xs uppercase tracking-wide text-slate-400">Updated At</p>
          <p class="mt-1 text-sm text-slate-200">{{ overrideMeta?.updated_at || '-' }}</p>
        </div>
      </div>

      <div class="rounded border border-slate-700 bg-slate-800 p-4">
        <div class="mb-4 rounded border border-slate-700 bg-slate-900 p-4">
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-400">Wizard</p>
            <h3 class="mt-1 text-lg font-semibold text-white">{{ wizardSteps[currentStep - 1]?.title }}</h3>
            <p class="text-sm text-slate-400">{{ wizardSteps[currentStep - 1]?.description }}</p>
          </div>

          <div class="mt-4 flex flex-col gap-2 border-t border-slate-700 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-xs text-slate-400">
              <span class="font-semibold text-slate-200">Module aktif:</span>
              {{ selectedModuleKey || '-' }}
            </div>

            <div class="flex gap-2">
              <button
                type="button"
                class="rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-600 disabled:cursor-not-allowed disabled:bg-slate-800 disabled:text-slate-500"
                :disabled="currentStep === 1"
                @click="prevStep"
              >
                Sebelumnya
              </button>
              <button
                type="button"
                class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-800 disabled:text-slate-500"
                :disabled="!canGoNext"
                @click="nextStep"
              >
                {{ currentStep === wizardSteps.length ? 'Selesai' : 'Berikutnya' }}
              </button>
            </div>
          </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[260px_minmax(0,1fr)]">
          <div>
            <label class="mb-2 block text-sm text-slate-300">Cari Module</label>
            <input v-model="search" type="text" placeholder="Cari module..." class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-white" />

            <div class="mt-3 max-h-[560px] space-y-2 overflow-auto pr-1">
              <button
                v-for="moduleKey in filteredModuleKeys"
                :key="moduleKey"
                type="button"
                class="w-full rounded border px-3 py-2 text-left text-sm transition"
                :class="selectedModuleKey === moduleKey ? 'border-indigo-500 bg-indigo-600/20 text-white' : 'border-slate-700 bg-slate-900 text-slate-300 hover:border-slate-600'"
                @click="selectedModuleKey = moduleKey"
              >
                <div class="flex items-center justify-between gap-2">
                  <span>{{ moduleKey }}</span>
                  <span class="rounded px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide" :class="isChanged(moduleKey) ? 'bg-amber-500/20 text-amber-300' : 'bg-emerald-500/20 text-emerald-300'">
                    {{ isChanged(moduleKey) ? 'Override' : 'Default' }}
                  </span>
                </div>
              </button>
            </div>
          </div>

          <div v-if="selectedModuleKey && selected" class="space-y-4">
            <div v-if="currentStep === 1" class="rounded border border-indigo-500/40 bg-indigo-600/10 p-6">
              <h3 class="text-lg font-semibold text-white">Step 1: Pilih Module</h3>
              <p class="mt-1 text-sm text-slate-300">
                Pilih module di panel kiri. Setelah itu Anda bisa lanjut ke compare, edit scope, edit ability, cek akses template spesifik bila ada, lalu audit.
              </p>
              <div class="mt-4 rounded border border-slate-700 bg-slate-900 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">Module Terpilih</p>
                <p class="mt-1 text-base font-semibold text-white">{{ selectedModuleKey }}</p>
                <p class="mt-1 text-sm text-slate-400">
                  Status:
                  <span :class="isChanged(selectedModuleKey) ? 'text-amber-300' : 'text-emerald-300'" class="font-semibold">
                    {{ isChanged(selectedModuleKey) ? 'Override Aktif' : 'Masih Default' }}
                  </span>
                </p>
              </div>
            </div>

            <div v-if="currentStep === 2" class="grid gap-4">
              <div class="rounded border border-slate-700 bg-slate-900 p-4">
                <div class="mb-3 flex items-center justify-between">
                  <h4 class="text-sm font-semibold text-slate-200">Scopes</h4>
                  <span class="text-xs text-slate-400">{{ selected.scopes.length }} item</span>
                </div>

                <div class="mb-3 flex flex-wrap gap-2">
                  <button type="button" class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600" @click="addScope">Add Scope</button>
                  <button type="button" class="rounded bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500" @click="currentStep = 3">Ke Abilities</button>
                </div>

                <div class="space-y-3">
                  <div v-for="(scope, scopeIndex) in selected.scopes" :key="`scope-${scopeIndex}`" class="rounded border border-slate-700 bg-slate-800 p-3">
                    <div class="flex gap-2">
                      <select v-model="scope.key" class="flex-1 rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                        <option value="">Pilih scope key</option>
                        <option v-for="option in scopeKeyOptions(scope.key)" :key="`scope-key-${option}`" :value="option">{{ option }}</option>
                      </select>
                      <button type="button" class="rounded bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500" @click="selected.scopes.splice(scopeIndex, 1)">Remove</button>
                    </div>

                    <p class="mt-2 text-xs text-slate-500">Default: {{ describeDefaultScope(scope.key) }}</p>

                    <div class="mt-3 rounded border border-slate-700 bg-slate-950/50 p-3">
                      <div class="mb-2 flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">all_if</p>
                        <button type="button" class="rounded bg-slate-700 px-2 py-1 text-[11px] font-semibold text-white hover:bg-slate-600" @click="scope.all_if.push(makeCondition())">Add Condition</button>
                      </div>
                      <p class="mb-3 text-[11px] text-slate-500">
                        Untuk menentukan siapa yang boleh akses semua data pada scope ini.
                      </p>

                      <div class="space-y-2">
                        <div v-for="(rule, ruleIndex) in scope.all_if" :key="`scope-rule-${scopeIndex}-${ruleIndex}`" class="grid gap-2 md:grid-cols-[170px_minmax(0,1fr)_auto]">
                          <select v-model="rule.type" class="rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                            <option v-for="option in typeOptions(rule.type)" :key="option.value" :value="option.value">{{ option.label }}</option>
                          </select>
                          <div>
                            <select
                              v-if="usesDropdownValue(rule.type)"
                              v-model="rule.value"
                              class="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                            >
                              <option value="">Pilih value</option>
                              <option v-for="option in valueOptionsForType(rule.type)" :key="`scope-single-${rule.type}-${option.value}`" :value="option.value">
                                {{ option.label }}
                              </option>
                            </select>
                            <input v-else-if="needsValue(rule.type)" v-model="rule.value" type="text" placeholder="value" class="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                            <div v-else-if="needsValues(rule.type)" class="space-y-2">
                              <div class="flex gap-2">
                                <select v-model="rule.pendingValue" class="flex-1 rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                                  <option value="">Pilih value</option>
                                  <option v-for="option in valueOptionsForType(rule.type, rule.values)" :key="`scope-multi-${rule.type}-${option.value}`" :value="option.value">
                                    {{ option.label }}
                                  </option>
                                </select>
                                <button
                                  type="button"
                                  class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600 disabled:cursor-not-allowed disabled:bg-slate-800 disabled:text-slate-500"
                                  :disabled="!rule.pendingValue"
                                  @click="addRuleValue(rule)"
                                >
                                  Add
                                </button>
                              </div>
                              <div class="flex flex-wrap gap-2">
                                <span
                                  v-for="(item, itemIndex) in rule.values"
                                  :key="`scope-multi-tag-${rule.type}-${item}`"
                                  class="inline-flex items-center gap-1 rounded bg-indigo-600/20 px-2 py-1 text-xs text-indigo-200"
                                >
                                  {{ item }}
                                  <button type="button" class="text-indigo-100 hover:text-white" @click="rule.values.splice(itemIndex, 1)">x</button>
                                </span>
                              </div>
                            </div>
                            <p v-else class="rounded border border-dashed border-slate-700 px-3 py-2 text-xs text-slate-400">Tanpa value.</p>
                          </div>
                          <button type="button" class="rounded bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500" @click="scope.all_if.splice(ruleIndex, 1)">Remove</button>
                        </div>
                        <p v-if="scope.all_if.length === 0" class="text-xs text-slate-500">Belum ada condition.</p>
                      </div>
                    </div>

                    <div class="mt-3 rounded border border-slate-700 bg-slate-950/50 p-3">
                      <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">ids_from</p>
                      <p class="mb-3 text-[11px] text-slate-500">
                        Untuk menentukan akses dasar diambil dari mana, misalnya departemen sendiri atau departemen yang dikelola.
                      </p>
                      <div class="space-y-2">
                        <div class="flex gap-2">
                          <select v-model="scope.pendingIdsFrom" class="flex-1 rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                            <option value="">Pilih ids_from</option>
                            <option v-for="option in availableIdsFromOptions(scope.ids_from)" :key="`ids-from-${option.value}`" :value="option.value">
                              {{ option.label }}
                            </option>
                          </select>
                          <button
                            type="button"
                            class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600 disabled:cursor-not-allowed disabled:bg-slate-800 disabled:text-slate-500"
                            :disabled="!scope.pendingIdsFrom"
                            @click="addIdsFrom(scope)"
                          >
                            Add
                          </button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                          <span
                            v-for="(item, itemIndex) in scope.ids_from"
                            :key="`ids-from-tag-${item}`"
                            class="inline-flex items-center gap-1 rounded bg-indigo-600/20 px-2 py-1 text-xs text-indigo-200"
                          >
                            {{ item }}
                            <button type="button" class="text-indigo-100 hover:text-white" @click="scope.ids_from.splice(itemIndex, 1)">x</button>
                          </span>
                        </div>
                      </div>
                      <div v-if="humanReadableIdsFrom(scope.ids_from)" class="mt-3 rounded border border-emerald-500/20 bg-emerald-500/10 p-2 text-[11px] text-emerald-200">
                        {{ humanReadableIdsFrom(scope.ids_from) }}
                      </div>
                    </div>

                    <div class="mt-3 rounded border border-slate-700 bg-slate-950/50 p-3">
                      <div class="mb-2 flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">append_ids_if</p>
                        <button type="button" class="rounded bg-slate-700 px-2 py-1 text-[11px] font-semibold text-white hover:bg-slate-600" @click="scope.append_ids_if.push(makeAppendRule())">Add Append</button>
                      </div>
                      <p class="mb-3 text-[11px] text-slate-500">
                        Untuk menambahkan akses departemen tertentu di luar akses dasar, jika kondisi tertentu terpenuhi.
                      </p>

                      <div class="space-y-3">
                        <div v-for="(appendRule, appendIndex) in scope.append_ids_if" :key="`append-${scopeIndex}-${appendIndex}`" class="rounded border border-slate-700 bg-slate-900 p-3">
                          <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Append {{ appendIndex + 1 }}</p>
                            <button type="button" class="rounded bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500" @click="scope.append_ids_if.splice(appendIndex, 1)">Remove</button>
                          </div>

                          <div class="mt-3 space-y-2">
                            <div class="mb-2 flex items-center justify-between">
                              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">if</p>
                              <button type="button" class="rounded bg-slate-700 px-2 py-1 text-[11px] font-semibold text-white hover:bg-slate-600" @click="appendRule.if.push(makeCondition())">Add Condition</button>
                            </div>
                            <div v-for="(rule, ruleIndex) in appendRule.if" :key="`append-rule-${scopeIndex}-${appendIndex}-${ruleIndex}`" class="grid gap-2 md:grid-cols-[170px_minmax(0,1fr)_auto]">
                              <select v-model="rule.type" class="rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                                <option v-for="option in typeOptions(rule.type)" :key="option.value" :value="option.value">{{ option.label }}</option>
                              </select>
                              <div>
                                <select
                                  v-if="usesDropdownValue(rule.type)"
                                  v-model="rule.value"
                                  class="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                                >
                                  <option value="">Pilih value</option>
                                  <option v-for="option in valueOptionsForType(rule.type)" :key="`append-single-${rule.type}-${option.value}`" :value="option.value">
                                    {{ option.label }}
                                  </option>
                                </select>
                                <input v-else-if="needsValue(rule.type)" v-model="rule.value" type="text" placeholder="value" class="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                                <div v-else-if="needsValues(rule.type)" class="space-y-2">
                                  <div class="flex gap-2">
                                    <select v-model="rule.pendingValue" class="flex-1 rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                                      <option value="">Pilih value</option>
                                      <option v-for="option in valueOptionsForType(rule.type, rule.values)" :key="`append-multi-${rule.type}-${option.value}`" :value="option.value">
                                        {{ option.label }}
                                      </option>
                                    </select>
                                    <button
                                      type="button"
                                      class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600 disabled:cursor-not-allowed disabled:bg-slate-800 disabled:text-slate-500"
                                      :disabled="!rule.pendingValue"
                                      @click="addRuleValue(rule)"
                                    >
                                      Add
                                    </button>
                                  </div>
                                  <div class="flex flex-wrap gap-2">
                                    <span
                                      v-for="(item, itemIndex) in rule.values"
                                      :key="`append-multi-tag-${rule.type}-${item}`"
                                      class="inline-flex items-center gap-1 rounded bg-indigo-600/20 px-2 py-1 text-xs text-indigo-200"
                                    >
                                      {{ item }}
                                      <button type="button" class="text-indigo-100 hover:text-white" @click="rule.values.splice(itemIndex, 1)">x</button>
                                    </span>
                                  </div>
                                </div>
                                <p v-else class="rounded border border-dashed border-slate-700 px-3 py-2 text-xs text-slate-400">Tanpa value.</p>
                              </div>
                              <button type="button" class="rounded bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500" @click="appendRule.if.splice(ruleIndex, 1)">Remove</button>
                            </div>
                          </div>

                          <div class="mt-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">department_codes</p>
                            <div class="space-y-2">
                              <div class="flex gap-2">
                                <select v-model="appendRule.pendingDepartmentCode" class="flex-1 rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                                  <option value="">Pilih department code</option>
                                  <option
                                    v-for="department in availableDepartmentCodeOptions(appendRule.department_codes)"
                                    :key="`dept-${scopeIndex}-${appendIndex}-${department.code}`"
                                    :value="department.code"
                                  >
                                    {{ department.code }} - {{ department.name }}
                                  </option>
                                </select>
                                <button
                                  type="button"
                                  class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600 disabled:cursor-not-allowed disabled:bg-slate-800 disabled:text-slate-500"
                                  :disabled="!appendRule.pendingDepartmentCode"
                                  @click="addDepartmentCode(appendRule)"
                                >
                                  Add
                                </button>
                              </div>
                              <div class="flex flex-wrap gap-2">
                                <span
                                  v-for="(departmentCode, departmentIndex) in appendRule.department_codes"
                                  :key="`selected-dept-${scopeIndex}-${appendIndex}-${departmentCode}`"
                                  class="inline-flex items-center gap-1 rounded bg-indigo-600/20 px-2 py-1 text-xs text-indigo-200"
                                >
                                  {{ departmentCode }}
                                  <button
                                    type="button"
                                    class="text-indigo-100 hover:text-white"
                                    @click="appendRule.department_codes.splice(departmentIndex, 1)"
                                  >
                                    x
                                  </button>
                                </span>
                              </div>
                            </div>
                          </div>
                        </div>
                        <p v-if="scope.append_ids_if.length === 0" class="text-xs text-slate-500">Belum ada append rule.</p>
                      </div>
                      <div v-if="humanReadableAppendRules(scope.append_ids_if).length" class="mt-3 space-y-2">
                        <div
                          v-for="(message, messageIndex) in humanReadableAppendRules(scope.append_ids_if)"
                          :key="`append-helper-${scopeIndex}-${messageIndex}`"
                          class="rounded border border-sky-500/20 bg-sky-500/10 p-2 text-[11px] text-sky-200"
                        >
                          {{ message }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="currentStep === 3" class="grid gap-4">
              <div v-if="selected.settings.length || base.settings.length || selectedModuleKey === 'attendance_log'" class="rounded border border-slate-700 bg-slate-900 p-4">
                <div class="mb-3 flex items-center justify-between">
                  <div>
                    <h4 class="text-sm font-semibold text-slate-200">Settings</h4>
                    <p class="text-xs text-slate-400">Atur parameter module yang tidak berbentuk scope atau ability.</p>
                  </div>
                  <span class="text-xs text-slate-400">{{ selected.settings.length }} item</span>
                </div>

                <div class="mb-3 flex flex-wrap gap-2">
                  <button type="button" class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600" @click="addSetting">Add Setting</button>
                </div>

                <div class="space-y-3">
                  <div
                    v-for="(setting, settingIndex) in selected.settings"
                    :key="`setting-${settingIndex}`"
                    class="rounded border border-slate-700 bg-slate-800 p-3"
                  >
                    <div class="flex gap-2">
                      <select v-model="setting.key" class="flex-1 rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                        <option value="">Pilih setting key</option>
                        <option v-for="option in settingKeyOptions(setting.key)" :key="`setting-key-${option}`" :value="option">{{ option }}</option>
                      </select>
                      <button type="button" class="rounded bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500" @click="selected.settings.splice(settingIndex, 1)">Remove</button>
                    </div>

                    <p class="mt-2 text-xs text-slate-500">Default: {{ describeDefaultSetting(setting.key) }}</p>

                    <div class="mt-3">
                      <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {{ settingLabel(setting.key) }}
                      </label>
                      <input
                        v-if="isBooleanSetting(setting)"
                        v-model="setting.value"
                        type="checkbox"
                        class="h-4 w-4 rounded border border-slate-700 bg-slate-950 text-indigo-600"
                      />
                      <input
                        v-else-if="isNumberSetting(setting)"
                        v-model.number="setting.value"
                        type="number"
                        min="0"
                        step="1"
                        class="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                      />
                      <input
                        v-else
                        v-model="setting.value"
                        type="text"
                        class="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                      />
                      <p v-if="setting.key === 'late_tolerance_minutes'" class="mt-2 text-[11px] text-sky-300">
                        Jumlah menit toleransi sebelum scan masuk dihitung sebagai terlambat.
                      </p>
                    </div>
                  </div>

                  <div v-if="selected.settings.length === 0" class="rounded border border-dashed border-slate-700 bg-slate-800 p-4 text-xs text-slate-400">
                    Module ini belum punya setting di draft.
                  </div>
                </div>
              </div>

              <div class="rounded border border-slate-700 bg-slate-900 p-4">
                <div class="mb-3 flex items-center justify-between">
                  <h4 class="text-sm font-semibold text-slate-200">Abilities</h4>
                  <span class="text-xs text-slate-400">{{ selected.abilities.length }} item</span>
                </div>

                <div class="mb-3 flex flex-wrap gap-2">
                  <button type="button" class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600" @click="addAbility">Add Ability</button>
                  <button type="button" class="rounded bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500" @click="currentStep = 4">Ke Audit Trail</button>
                </div>

                <div class="space-y-3">
                  <div v-for="(ability, abilityIndex) in selected.abilities" :key="`ability-${abilityIndex}`" class="rounded border border-slate-700 bg-slate-800 p-3">
                    <div class="flex gap-2">
                      <select v-model="ability.key" class="flex-1 rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                        <option value="">Pilih ability key</option>
                        <option v-for="option in abilityKeyOptions(ability.key)" :key="`ability-key-${option}`" :value="option">{{ option }}</option>
                      </select>
                      <button type="button" class="rounded bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500" @click="selected.abilities.splice(abilityIndex, 1)">Remove</button>
                    </div>

                    <p class="mt-2 text-xs text-slate-500">Default: {{ describeDefaultAbility(ability.key) }}</p>

                    <div class="mt-3 space-y-2">
                      <div class="mb-2 flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">conditions</p>
                        <button type="button" class="rounded bg-slate-700 px-2 py-1 text-[11px] font-semibold text-white hover:bg-slate-600" @click="ability.conditions.push(makeCondition())">Add Condition</button>
                      </div>

                      <div v-for="(rule, ruleIndex) in ability.conditions" :key="`ability-rule-${abilityIndex}-${ruleIndex}`" class="grid gap-2 md:grid-cols-[170px_minmax(0,1fr)_auto]">
                        <select v-model="rule.type" class="rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                          <option v-for="option in typeOptions(rule.type)" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <div>
                          <select
                            v-if="usesDropdownValue(rule.type)"
                            v-model="rule.value"
                            class="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                          >
                            <option value="">Pilih value</option>
                            <option v-for="option in valueOptionsForType(rule.type)" :key="`ability-single-${rule.type}-${option.value}`" :value="option.value">
                              {{ option.label }}
                            </option>
                          </select>
                          <input v-else-if="needsValue(rule.type)" v-model="rule.value" type="text" placeholder="value" class="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                          <div v-else-if="needsValues(rule.type)" class="space-y-2">
                            <div class="flex gap-2">
                              <select v-model="rule.pendingValue" class="flex-1 rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                                <option value="">Pilih value</option>
                                <option v-for="option in valueOptionsForType(rule.type, rule.values)" :key="`ability-multi-${rule.type}-${option.value}`" :value="option.value">
                                  {{ option.label }}
                                </option>
                              </select>
                              <button
                                type="button"
                                class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600 disabled:cursor-not-allowed disabled:bg-slate-800 disabled:text-slate-500"
                                :disabled="!rule.pendingValue"
                                @click="addRuleValue(rule)"
                              >
                                Add
                              </button>
                            </div>
                            <div class="flex flex-wrap gap-2">
                              <span
                                v-for="(item, itemIndex) in rule.values"
                                :key="`ability-multi-tag-${rule.type}-${item}`"
                                class="inline-flex items-center gap-1 rounded bg-indigo-600/20 px-2 py-1 text-xs text-indigo-200"
                              >
                                {{ item }}
                                <button type="button" class="text-indigo-100 hover:text-white" @click="rule.values.splice(itemIndex, 1)">x</button>
                              </span>
                            </div>
                          </div>
                          <p v-else class="rounded border border-dashed border-slate-700 px-3 py-2 text-xs text-slate-400">Tanpa value.</p>
                        </div>
                        <button type="button" class="rounded bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500" @click="ability.conditions.splice(ruleIndex, 1)">Remove</button>
                      </div>
                      <p v-if="ability.conditions.length === 0" class="text-xs text-slate-500">Belum ada condition.</p>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="selected.template_permissions.length || base.template_permissions.length" class="rounded border border-slate-700 bg-slate-900 p-4">
                <div class="mb-3 flex items-center justify-between">
                  <div>
                    <h4 class="text-sm font-semibold text-slate-200">Template Permissions</h4>
                    <p class="text-xs text-slate-400">Atur akses `view` dan `approve` untuk template spesifik dalam module ini.</p>
                  </div>
                  <span class="text-xs text-slate-400">{{ selected.template_permissions.length }} item</span>
                </div>

                <div class="mb-3 flex flex-wrap gap-2">
                  <button type="button" class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600" @click="addTemplatePermission">Add Template</button>
                </div>

                <div class="space-y-3">
                  <div
                    v-for="(templatePermission, templateIndex) in selected.template_permissions"
                    :key="`template-permission-${templateIndex}`"
                    class="rounded border border-slate-700 bg-slate-800 p-3"
                  >
                    <div class="flex gap-2">
                      <select v-model="templatePermission.key" class="flex-1 rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                        <option value="">Pilih template key</option>
                        <option v-for="option in templatePermissionKeyOptions(templatePermission.key)" :key="`template-key-${option}`" :value="option">{{ option }}</option>
                      </select>
                      <button type="button" class="rounded bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500" @click="selected.template_permissions.splice(templateIndex, 1)">Remove</button>
                    </div>

                    <p class="mt-2 text-xs text-slate-500">Default view: {{ describeDefaultTemplatePermission(templatePermission.key, 'view') }}</p>
                    <p class="mt-1 text-xs text-slate-500">Default approve: {{ describeDefaultTemplatePermission(templatePermission.key, 'approve') }}</p>

                    <div class="mt-3 grid gap-3 xl:grid-cols-2">
                      <div class="rounded border border-slate-700 bg-slate-950/50 p-3">
                        <div class="mb-2 flex items-center justify-between">
                          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">view</p>
                          <button type="button" class="rounded bg-slate-700 px-2 py-1 text-[11px] font-semibold text-white hover:bg-slate-600" @click="templatePermission.view_conditions.push(makeCondition())">Add Condition</button>
                        </div>

                        <div class="space-y-2">
                          <div
                            v-for="(rule, ruleIndex) in templatePermission.view_conditions"
                            :key="`template-view-${templateIndex}-${ruleIndex}`"
                            class="grid gap-2 md:grid-cols-[170px_minmax(0,1fr)_auto]"
                          >
                            <select v-model="rule.type" class="rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                              <option v-for="option in typeOptions(rule.type)" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                            <div>
                              <select
                                v-if="usesDropdownValue(rule.type)"
                                v-model="rule.value"
                                class="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                              >
                                <option value="">Pilih value</option>
                                <option v-for="option in valueOptionsForType(rule.type)" :key="`template-view-single-${rule.type}-${option.value}`" :value="option.value">
                                  {{ option.label }}
                                </option>
                              </select>
                              <input v-else-if="needsValue(rule.type)" v-model="rule.value" type="text" placeholder="value" class="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                              <div v-else-if="needsValues(rule.type)" class="space-y-2">
                                <div class="flex gap-2">
                                  <select v-model="rule.pendingValue" class="flex-1 rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                                    <option value="">Pilih value</option>
                                    <option v-for="option in valueOptionsForType(rule.type, rule.values)" :key="`template-view-multi-${rule.type}-${option.value}`" :value="option.value">
                                      {{ option.label }}
                                    </option>
                                  </select>
                                  <button
                                    type="button"
                                    class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600 disabled:cursor-not-allowed disabled:bg-slate-800 disabled:text-slate-500"
                                    :disabled="!rule.pendingValue"
                                    @click="addRuleValue(rule)"
                                  >
                                    Add
                                  </button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                  <span
                                    v-for="(item, itemIndex) in rule.values"
                                    :key="`template-view-multi-tag-${rule.type}-${item}`"
                                    class="inline-flex items-center gap-1 rounded bg-indigo-600/20 px-2 py-1 text-xs text-indigo-200"
                                  >
                                    {{ item }}
                                    <button type="button" class="text-indigo-100 hover:text-white" @click="rule.values.splice(itemIndex, 1)">x</button>
                                  </span>
                                </div>
                              </div>
                              <p v-else class="rounded border border-dashed border-slate-700 px-3 py-2 text-xs text-slate-400">Tanpa value.</p>
                            </div>
                            <button type="button" class="rounded bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500" @click="templatePermission.view_conditions.splice(ruleIndex, 1)">Remove</button>
                          </div>
                          <p v-if="templatePermission.view_conditions.length === 0" class="text-xs text-slate-500">Belum ada condition untuk view.</p>
                        </div>
                      </div>

                      <div class="rounded border border-slate-700 bg-slate-950/50 p-3">
                        <div class="mb-2 flex items-center justify-between">
                          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">approve</p>
                          <button type="button" class="rounded bg-slate-700 px-2 py-1 text-[11px] font-semibold text-white hover:bg-slate-600" @click="templatePermission.approve_conditions.push(makeCondition())">Add Condition</button>
                        </div>

                        <div class="space-y-2">
                          <div
                            v-for="(rule, ruleIndex) in templatePermission.approve_conditions"
                            :key="`template-approve-${templateIndex}-${ruleIndex}`"
                            class="grid gap-2 md:grid-cols-[170px_minmax(0,1fr)_auto]"
                          >
                            <select v-model="rule.type" class="rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                              <option v-for="option in typeOptions(rule.type)" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                            <div>
                              <select
                                v-if="usesDropdownValue(rule.type)"
                                v-model="rule.value"
                                class="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                              >
                                <option value="">Pilih value</option>
                                <option v-for="option in valueOptionsForType(rule.type)" :key="`template-approve-single-${rule.type}-${option.value}`" :value="option.value">
                                  {{ option.label }}
                                </option>
                              </select>
                              <input v-else-if="needsValue(rule.type)" v-model="rule.value" type="text" placeholder="value" class="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white" />
                              <div v-else-if="needsValues(rule.type)" class="space-y-2">
                                <div class="flex gap-2">
                                  <select v-model="rule.pendingValue" class="flex-1 rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                                    <option value="">Pilih value</option>
                                    <option v-for="option in valueOptionsForType(rule.type, rule.values)" :key="`template-approve-multi-${rule.type}-${option.value}`" :value="option.value">
                                      {{ option.label }}
                                    </option>
                                  </select>
                                  <button
                                    type="button"
                                    class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600 disabled:cursor-not-allowed disabled:bg-slate-800 disabled:text-slate-500"
                                    :disabled="!rule.pendingValue"
                                    @click="addRuleValue(rule)"
                                  >
                                    Add
                                  </button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                  <span
                                    v-for="(item, itemIndex) in rule.values"
                                    :key="`template-approve-multi-tag-${rule.type}-${item}`"
                                    class="inline-flex items-center gap-1 rounded bg-indigo-600/20 px-2 py-1 text-xs text-indigo-200"
                                  >
                                    {{ item }}
                                    <button type="button" class="text-indigo-100 hover:text-white" @click="rule.values.splice(itemIndex, 1)">x</button>
                                  </span>
                                </div>
                              </div>
                              <p v-else class="rounded border border-dashed border-slate-700 px-3 py-2 text-xs text-slate-400">Tanpa value.</p>
                            </div>
                            <button type="button" class="rounded bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-500" @click="templatePermission.approve_conditions.splice(ruleIndex, 1)">Remove</button>
                          </div>
                          <p v-if="templatePermission.approve_conditions.length === 0" class="text-xs text-slate-500">Belum ada condition untuk approve.</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-if="selected.template_permissions.length === 0" class="rounded border border-dashed border-slate-700 bg-slate-800 p-4 text-xs text-slate-400">
                    Module ini belum punya template permission di draft.
                  </div>
                </div>
              </div>
            </div>

            <div v-if="currentStep === 4" class="rounded border border-slate-700 bg-slate-900 p-4">
              <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                  <h4 class="text-sm font-semibold text-slate-200">Audit Trail</h4>
                  <p class="text-xs text-slate-400">Riwayat save dan reset access rule.</p>
                </div>
                <span class="text-xs text-slate-500">{{ filteredAuditTrail.length }} / {{ auditTrail.length }} entry</span>
              </div>

              <div class="mb-3 grid gap-3 md:grid-cols-[220px_minmax(0,1fr)]">
                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Filter Module</label>
                  <select v-model="auditModuleFilter" class="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white">
                    <option value="">Semua Module</option>
                    <option v-for="moduleKey in auditModuleOptions" :key="`audit-${moduleKey}`" :value="moduleKey">
                      {{ moduleKey }}
                    </option>
                  </select>
                </div>
                <div class="rounded border border-slate-700 bg-slate-800 p-3 text-xs text-slate-400">
                  Filter ini menampilkan hanya audit yang menyentuh module terkait, lalu detail diff akan fokus ke rule di module itu.
                </div>
              </div>

              <div class="space-y-3">
                <div v-for="entry in filteredAuditTrail" :key="entry.id" class="rounded border border-slate-700 bg-slate-800 p-3">
                  <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                      <div class="flex items-center gap-2">
                        <span class="rounded px-2 py-1 text-[10px] font-semibold uppercase tracking-wide" :class="auditActionBadgeClass(entry.action)">
                          {{ auditActionLabel(entry.action) }}
                        </span>
                        <p class="text-sm font-semibold text-white">
                          {{ auditActionTitle(entry.action) }}
                        </p>
                      </div>
                      <p class="text-xs text-slate-400">
                        {{ entry.performed_at }} • {{ entry.user?.name || 'System' }}{{ entry.user?.email ? ` (${entry.user.email})` : '' }}
                      </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-[11px]">
                      <span class="rounded bg-slate-700 px-2 py-1 text-slate-200">Before: {{ entry.summary?.total_modules_before ?? 0 }}</span>
                      <span class="rounded bg-slate-700 px-2 py-1 text-slate-200">After: {{ entry.summary?.total_modules_after ?? 0 }}</span>
                      <button
                        type="button"
                        class="rounded bg-sky-600 px-3 py-1 text-white hover:bg-sky-500 disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300"
                        :disabled="!canRollbackEntry(entry)"
                        @click="previewRollback(entry)"
                      >
                        Preview Rollback
                      </button>
                      <button
                        type="button"
                        class="rounded bg-amber-600 px-3 py-1 text-white hover:bg-amber-500 disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300"
                        :disabled="!canRollbackEntry(entry)"
                        @click="rollbackAudit(entry)"
                      >
                        Rollback
                      </button>
                      <button type="button" class="rounded bg-slate-700 px-3 py-1 text-slate-100 hover:bg-slate-600" @click="toggleAuditExpand(entry.id)">
                        {{ expandedAuditIds.includes(entry.id) ? 'Sembunyikan Detail' : 'Lihat Detail' }}
                      </button>
                    </div>
                  </div>

                  <div class="mt-3 grid gap-3 md:grid-cols-3">
                    <div class="rounded border border-slate-700 bg-slate-900 p-2">
                      <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Added Modules</p>
                      <p class="mt-1 text-xs text-slate-300">{{ formatAuditList(entry.summary?.added_modules) }}</p>
                    </div>
                    <div class="rounded border border-slate-700 bg-slate-900 p-2">
                      <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Removed Modules</p>
                      <p class="mt-1 text-xs text-slate-300">{{ formatAuditList(entry.summary?.removed_modules) }}</p>
                    </div>
                    <div class="rounded border border-slate-700 bg-slate-900 p-2">
                      <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Changed Modules</p>
                      <p class="mt-1 text-xs text-slate-300">{{ formatChangedModules(entry.summary?.changed_modules) }}</p>
                    </div>
                  </div>

                  <div v-if="expandedAuditIds.includes(entry.id)" class="mt-3 space-y-3">
                    <div
                      v-for="moduleChange in filteredChangedModules(entry.summary)"
                      :key="`${entry.id}-${moduleChange.module}`"
                      class="rounded border border-slate-700 bg-slate-900 p-3"
                    >
                      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                          <p class="text-sm font-semibold text-white">{{ moduleChange.module }}</p>
                          <p class="text-xs text-slate-400">{{ detailHeader(moduleChange) }}</p>
                        </div>
                        <span class="rounded bg-indigo-600/20 px-2 py-1 text-[11px] font-semibold text-indigo-200">
                          {{ moduleChangeCount(moduleChange) }} perubahan
                        </span>
                      </div>

                      <div class="mt-3 grid gap-3 xl:grid-cols-3">
                        <div class="rounded border border-slate-700 bg-slate-800 p-3">
                          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Scopes</p>
                          <div class="mt-2 space-y-3">
                            <div v-if="moduleChange.scopes_added?.length" class="text-xs text-slate-300">
                              <span class="font-semibold text-emerald-300">Added:</span> {{ moduleChange.scopes_added.join(', ') }}
                            </div>
                            <div v-if="moduleChange.scopes_removed?.length" class="text-xs text-slate-300">
                              <span class="font-semibold text-rose-300">Removed:</span> {{ moduleChange.scopes_removed.join(', ') }}
                            </div>
                            <div
                              v-for="ruleDiff in moduleChange.scopes_changed || []"
                              :key="`${entry.id}-${moduleChange.module}-scope-${ruleDiff.key}`"
                              class="rounded border border-slate-700 bg-slate-900 p-3"
                            >
                              <p class="text-sm font-semibold text-white">{{ ruleDiff.key }}</p>
                              <div class="mt-2 grid gap-3 lg:grid-cols-2">
                                <div>
                                  <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Before</p>
                                  <pre class="overflow-auto rounded bg-slate-950 p-2 text-[11px] text-slate-300">{{ formatRuleValue(ruleDiff.before) }}</pre>
                                </div>
                                <div>
                                  <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">After</p>
                                  <pre class="overflow-auto rounded bg-slate-950 p-2 text-[11px] text-slate-300">{{ formatRuleValue(ruleDiff.after) }}</pre>
                                </div>
                              </div>
                            </div>
                            <p v-if="!moduleChange.scopes_added?.length && !moduleChange.scopes_removed?.length && !(moduleChange.scopes_changed || []).length" class="text-xs text-slate-500">
                              Tidak ada perubahan scope.
                            </p>
                          </div>
                        </div>

                        <div class="rounded border border-slate-700 bg-slate-800 p-3">
                          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Abilities</p>
                          <div class="mt-2 space-y-3">
                            <div v-if="moduleChange.abilities_added?.length" class="text-xs text-slate-300">
                              <span class="font-semibold text-emerald-300">Added:</span> {{ moduleChange.abilities_added.join(', ') }}
                            </div>
                            <div v-if="moduleChange.abilities_removed?.length" class="text-xs text-slate-300">
                              <span class="font-semibold text-rose-300">Removed:</span> {{ moduleChange.abilities_removed.join(', ') }}
                            </div>
                            <div
                              v-for="ruleDiff in moduleChange.abilities_changed || []"
                              :key="`${entry.id}-${moduleChange.module}-ability-${ruleDiff.key}`"
                              class="rounded border border-slate-700 bg-slate-900 p-3"
                            >
                              <p class="text-sm font-semibold text-white">{{ ruleDiff.key }}</p>
                              <div class="mt-2 grid gap-3 lg:grid-cols-2">
                                <div>
                                  <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Before</p>
                                  <pre class="overflow-auto rounded bg-slate-950 p-2 text-[11px] text-slate-300">{{ formatRuleValue(ruleDiff.before) }}</pre>
                                </div>
                                <div>
                                  <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">After</p>
                                  <pre class="overflow-auto rounded bg-slate-950 p-2 text-[11px] text-slate-300">{{ formatRuleValue(ruleDiff.after) }}</pre>
                                </div>
                              </div>
                            </div>
                            <p v-if="!moduleChange.abilities_added?.length && !moduleChange.abilities_removed?.length && !(moduleChange.abilities_changed || []).length" class="text-xs text-slate-500">
                              Tidak ada perubahan ability.
                            </p>
                          </div>
                        </div>

                        <div class="rounded border border-slate-700 bg-slate-800 p-3">
                          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Settings</p>
                          <div class="mt-2 space-y-3">
                            <div v-if="moduleChange.settings_added?.length" class="text-xs text-slate-300">
                              <span class="font-semibold text-emerald-300">Added:</span> {{ moduleChange.settings_added.join(', ') }}
                            </div>
                            <div v-if="moduleChange.settings_removed?.length" class="text-xs text-slate-300">
                              <span class="font-semibold text-rose-300">Removed:</span> {{ moduleChange.settings_removed.join(', ') }}
                            </div>
                            <div
                              v-for="ruleDiff in moduleChange.settings_changed || []"
                              :key="`${entry.id}-${moduleChange.module}-setting-${ruleDiff.key}`"
                              class="rounded border border-slate-700 bg-slate-900 p-3"
                            >
                              <p class="text-sm font-semibold text-white">{{ ruleDiff.key }}</p>
                              <div class="mt-2 grid gap-3 lg:grid-cols-2">
                                <div>
                                  <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Before</p>
                                  <pre class="overflow-auto rounded bg-slate-950 p-2 text-[11px] text-slate-300">{{ formatRuleValue(ruleDiff.before) }}</pre>
                                </div>
                                <div>
                                  <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">After</p>
                                  <pre class="overflow-auto rounded bg-slate-950 p-2 text-[11px] text-slate-300">{{ formatRuleValue(ruleDiff.after) }}</pre>
                                </div>
                              </div>
                            </div>
                            <p v-if="!moduleChange.settings_added?.length && !moduleChange.settings_removed?.length && !(moduleChange.settings_changed || []).length" class="text-xs text-slate-500">
                              Tidak ada perubahan setting.
                            </p>
                          </div>
                        </div>

                        <div class="rounded border border-slate-700 bg-slate-800 p-3">
                          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Template Permissions</p>
                          <div class="mt-2 space-y-3">
                            <div v-if="moduleChange.template_permissions_added?.length" class="text-xs text-slate-300">
                              <span class="font-semibold text-emerald-300">Added:</span> {{ moduleChange.template_permissions_added.join(', ') }}
                            </div>
                            <div v-if="moduleChange.template_permissions_removed?.length" class="text-xs text-slate-300">
                              <span class="font-semibold text-rose-300">Removed:</span> {{ moduleChange.template_permissions_removed.join(', ') }}
                            </div>
                            <div
                              v-for="ruleDiff in moduleChange.template_permissions_changed || []"
                              :key="`${entry.id}-${moduleChange.module}-template-${ruleDiff.key}`"
                              class="rounded border border-slate-700 bg-slate-900 p-3"
                            >
                              <p class="text-sm font-semibold text-white">{{ ruleDiff.key }}</p>
                              <div class="mt-2 grid gap-3 lg:grid-cols-2">
                                <div>
                                  <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Before</p>
                                  <pre class="overflow-auto rounded bg-slate-950 p-2 text-[11px] text-slate-300">{{ formatRuleValue(ruleDiff.before) }}</pre>
                                </div>
                                <div>
                                  <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">After</p>
                                  <pre class="overflow-auto rounded bg-slate-950 p-2 text-[11px] text-slate-300">{{ formatRuleValue(ruleDiff.after) }}</pre>
                                </div>
                              </div>
                            </div>
                            <p v-if="!moduleChange.template_permissions_added?.length && !moduleChange.template_permissions_removed?.length && !(moduleChange.template_permissions_changed || []).length" class="text-xs text-slate-500">
                              Tidak ada perubahan template permission.
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div v-if="filteredChangedModules(entry.summary).length === 0" class="rounded border border-dashed border-slate-700 bg-slate-800 p-3 text-xs text-slate-400">
                      Tidak ada detail perubahan untuk filter module yang dipilih.
                    </div>
                  </div>
                </div>

                <div v-if="filteredAuditTrail.length === 0" class="rounded border border-dashed border-slate-700 bg-slate-800 p-4 text-sm text-slate-400">
                  Belum ada audit trail perubahan access rule.
                </div>
              </div>
            </div>
          </div>

          <div v-else class="rounded border border-dashed border-slate-700 bg-slate-900 p-8 text-center text-slate-400">Pilih module di sebelah kiri untuk mulai edit access rule.</div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, defineComponent, h, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { swalConfirm } from '@/Utils/swalConfirm';

const types = [
  ['admin', 'Admin'],
  ['manager', 'Manager'],
  ['supervisor', 'Supervisor'],
  ['user_id', 'User ID'],
  ['user_email', 'User Email'],
  ['user_account', 'User Account'],
  ['user_name_contains', 'User Name Contains'],
  ['department_code', 'Department Code'],
  ['department_name_contains', 'Department Name Contains'],
  ['position_code', 'Position Code'],
  ['position_name_contains', 'Position Name Contains'],
  ['manager_in_department_codes', 'Manager In Department Codes'],
];
const valueTypes = new Set([
  'user_id',
  'user_email',
  'user_account',
  'user_name_contains',
  'department_code',
  'department_name_contains',
  'position_code',
  'position_name_contains',
]);
const valuesTypes = new Set(['manager_in_department_codes']);
const idsFromOptions = [
  { value: 'own_department', label: 'own_department' },
  { value: 'managed_departments', label: 'managed_departments' },
];

const TagEditor = defineComponent({
  props: { items: { type: Array, default: () => [] }, pending: { type: String, default: '' }, placeholder: { type: String, default: '' } },
  emits: ['update:items', 'update:pending'],
  setup(props, { emit }) {
    const add = () => {
      const value = String(props.pending || '').trim();
      if (!value) return;
      const next = [...props.items];
      if (!next.includes(value)) next.push(value);
      emit('update:items', next);
      emit('update:pending', '');
    };
    const remove = (index) => emit('update:items', props.items.filter((_, i) => i !== index));
    return () => h('div', { class: 'space-y-2' }, [
      h('div', { class: 'flex flex-wrap gap-2' }, props.items.map((item, index) =>
        h('span', { class: 'inline-flex items-center gap-1 rounded bg-indigo-600/20 px-2 py-1 text-xs text-indigo-200' }, [
          item,
          h('button', { type: 'button', class: 'text-indigo-100 hover:text-white', onClick: () => remove(index) }, 'x'),
        ])
      )),
      h('div', { class: 'flex gap-2' }, [
        h('input', {
          value: props.pending,
          type: 'text',
          placeholder: props.placeholder,
          class: 'flex-1 rounded border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white',
          onInput: (event) => emit('update:pending', event.target.value),
          onKeydown: (event) => { if (event.key === 'Enter') { event.preventDefault(); add(); } },
        }),
        h('button', { type: 'button', class: 'rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600', onClick: add }, 'Add'),
      ]),
    ]);
  },
});

const props = defineProps({
  modules: { type: Object, default: () => ({}) },
  defaultModules: { type: Object, default: () => ({}) },
  overrideMeta: { type: Object, default: () => ({}) },
  auditTrail: { type: Array, default: () => [] },
  departmentCodes: { type: Array, default: () => [] },
  positionCodes: { type: Array, default: () => [] },
});

const search = ref('');
const states = ref(makeStates(props.modules));
const defaultStates = makeStates(props.defaultModules);
const selectedModuleKey = ref(Object.keys(states.value)[0] || '');
const auditModuleFilter = ref('');
const expandedAuditIds = ref([]);
const currentStep = ref(1);
const wizardSteps = [
  { number: 1, short: 'Module', title: 'Pilih Module', description: 'Tentukan module yang ingin diatur terlebih dahulu.' },
  { number: 2, short: 'Scopes', title: 'Atur Scope', description: 'Kelola siapa yang bisa melihat data dan jangkauan departemennya.' },
  { number: 3, short: 'Abilities', title: 'Atur Ability, Setting & Template', description: 'Kelola aksi module, parameter setting, dan template spesifik bila tersedia.' },
  { number: 4, short: 'Audit', title: 'Audit & Rollback', description: 'Tinjau riwayat perubahan dan rollback bila diperlukan.' },
];

const filteredModuleKeys = computed(() => {
  const query = String(search.value || '').trim().toLowerCase();
  return Object.keys(states.value).filter((key) => !query || key.toLowerCase().includes(query));
});
const selected = computed(() => states.value[selectedModuleKey.value] || null);
const base = computed(() => defaultStates[selectedModuleKey.value] || { scopes: [], abilities: [], settings: [], template_permissions: [] });
const hasSelectedModule = computed(() => Boolean(selectedModuleKey.value && selected.value));
const canGoNext = computed(() => {
  if (currentStep.value >= wizardSteps.length) {
    return false;
  }

  if (currentStep.value === 1) {
    return hasSelectedModule.value;
  }

  return true;
});
const auditModuleOptions = computed(() => {
  const modules = new Set();

  props.auditTrail.forEach((entry) => {
    const touched = Array.isArray(entry.summary?.touched_modules) ? entry.summary.touched_modules : [];
    touched.forEach((moduleKey) => modules.add(moduleKey));
  });

  return Array.from(modules).sort();
});
const filteredAuditTrail = computed(() => {
  const moduleKey = String(auditModuleFilter.value || '').trim();

  if (!moduleKey) {
    return props.auditTrail;
  }

  return props.auditTrail.filter((entry) => {
    const touched = Array.isArray(entry.summary?.touched_modules) ? entry.summary.touched_modules : [];
    return touched.includes(moduleKey);
  });
});

function makeStates(modules) {
  return Object.fromEntries(Object.entries(modules || {}).map(([moduleKey, config]) => [moduleKey, {
    scopes: Object.entries(config?.scopes || {}).map(([key, value]) => ({
      key,
      all_if: makeConditions(value?.all_if || []),
      ids_from: cleanArray(value?.ids_from || []),
      append_ids_if: (Array.isArray(value?.append_ids_if) ? value.append_ids_if : []).map((item) => ({
        if: makeConditions(item?.if || []),
        department_codes: cleanArray(item?.department_codes || []),
        pendingDepartmentCode: '',
      })),
      pendingIdsFrom: '',
    })),
    abilities: Object.entries(config?.abilities || {}).map(([key, value]) => ({
      key,
      conditions: makeConditions(value || []),
    })),
    settings: Object.entries(config?.settings || {}).map(([key, value]) => ({
      key,
      value,
    })),
    template_permissions: Object.entries(config?.template_permissions || {}).map(([key, value]) => ({
      key,
      view_conditions: makeConditions(value?.view || []),
      approve_conditions: makeConditions(value?.approve || []),
    })),
  }]));
}

function cleanArray(values) {
  return (Array.isArray(values) ? values : []).map((value) => String(value || '').trim()).filter(Boolean);
}

function makeConditions(conditions) {
  return (Array.isArray(conditions) ? conditions : []).map((item) => ({
    type: String(item?.type || 'department_code').trim() || 'department_code',
    value: typeof item?.value === 'string' ? item.value : '',
    values: cleanArray(item?.values || []),
    pendingValue: '',
  }));
}

function makeCondition() {
  return { type: 'department_code', value: '', values: [], pendingValue: '' };
}

function makeAppendRule() {
  return { if: [], department_codes: [], pendingDepartmentCode: '' };
}

function typeOptions(currentType) {
  return types.some(([value]) => value === currentType) ? types.map(([value, label]) => ({ value, label })) : [...types.map(([value, label]) => ({ value, label })), { value: currentType, label: `Custom: ${currentType}` }];
}

function needsValue(type) {
  return valueTypes.has(type);
}

function needsValues(type) {
  return valuesTypes.has(type);
}

function usesDropdownValue(type) {
  return needsValue(type) && valueOptionsForType(type).length > 0;
}

function valueOptionsForType(type, selectedValues = []) {
  const selected = new Set(cleanArray(selectedValues));

  if (type === 'department_code' || type === 'manager_in_department_codes') {
    return (props.departmentCodes || [])
      .map((department) => {
        const code = String(department?.code || '').trim();
        const name = String(department?.name || '').trim();
        return { value: code, label: name ? `${code} - ${name}` : code };
      })
      .filter((option) => option.value !== '' && !selected.has(option.value));
  }

  if (type === 'department_name_contains') {
    return (props.departmentCodes || [])
      .map((department) => {
        const name = String(department?.name || '').trim();
        return { value: name.toUpperCase(), label: name };
      })
      .filter((option) => option.value !== '' && !selected.has(option.value));
  }

  if (type === 'position_code') {
    return (props.positionCodes || [])
      .map((position) => {
        const code = String(position?.code || '').trim();
        const name = String(position?.name || '').trim();
        return { value: code, label: name ? `${code} - ${name}` : code };
      })
      .filter((option) => option.value !== '' && !selected.has(option.value));
  }

  if (type === 'position_name_contains') {
    return (props.positionCodes || [])
      .map((position) => {
        const name = String(position?.name || '').trim();
        return { value: name.toUpperCase(), label: name };
      })
      .filter((option) => option.value !== '' && !selected.has(option.value));
  }

  return [];
}

function addRuleValue(rule) {
  const value = String(rule?.pendingValue || '').trim();
  if (!value) {
    return;
  }

  const existing = new Set(cleanArray(rule.values || []));
  if (!existing.has(value)) {
    rule.values.push(value);
  }
  rule.pendingValue = '';
}

function normalizeCondition(rule) {
  const next = { type: String(rule?.type || '').trim() };
  if (needsValue(next.type)) {
    const value = String(rule?.value || '').trim();
    if (value) next.value = value;
  }
  if (needsValues(next.type)) {
    const values = cleanArray(rule?.values || []);
    if (values.length) next.values = values;
  }
  return next;
}

function settingType(settingOrKey) {
  const key = typeof settingOrKey === 'string' ? settingOrKey : String(settingOrKey?.key || '').trim();
  const defaultItem = (base.value.settings || []).find((setting) => setting.key === key);
  const currentItem = (selected.value?.settings || []).find((setting) => setting !== settingOrKey && setting.key === key);
  const referenceValue = defaultItem?.value ?? currentItem?.value;

  if (key === 'late_tolerance_minutes') {
    return 'number';
  }

  if (typeof referenceValue === 'boolean') {
    return 'boolean';
  }

  if (typeof referenceValue === 'number') {
    return 'number';
  }

  return 'text';
}

function isBooleanSetting(setting) {
  return settingType(setting) === 'boolean';
}

function isNumberSetting(setting) {
  return settingType(setting) === 'number';
}

function normalizeSettingValue(setting) {
  const key = String(setting?.key || '').trim();
  const type = settingType(setting);
  const rawValue = setting?.value;

  if (key === 'late_tolerance_minutes') {
    const parsed = Number.parseInt(String(rawValue ?? '0'), 10);
    return Number.isNaN(parsed) ? 0 : Math.max(0, parsed);
  }

  if (type === 'boolean') {
    return Boolean(rawValue);
  }

  if (type === 'number') {
    const parsed = Number(rawValue);
    return Number.isFinite(parsed) ? parsed : 0;
  }

  return String(rawValue ?? '').trim();
}

function formatSettingValue(value) {
  if (typeof value === 'boolean') {
    return value ? 'true' : 'false';
  }

  if (value === null || value === undefined || value === '') {
    return 'Kosong';
  }

  return String(value);
}

function settingLabel(settingKey) {
  return settingKey === 'late_tolerance_minutes' ? 'Toleransi Keterlambatan (menit)' : 'Value';
}

function serialize() {
  return Object.fromEntries(Object.entries(states.value).map(([moduleKey, config]) => {
    const moduleData = {};
    const scopes = Object.fromEntries((config.scopes || []).filter((scope) => String(scope.key || '').trim()).map((scope) => {
      const data = {};
      const allIf = (scope.all_if || []).filter((rule) => String(rule.type || '').trim()).map(normalizeCondition);
      const idsFrom = cleanArray(scope.ids_from || []);
      const appendIdsIf = (scope.append_ids_if || []).map((item) => ({
        if: (item.if || []).filter((rule) => String(rule.type || '').trim()).map(normalizeCondition),
        department_codes: cleanArray(item.department_codes || []),
      })).filter((item) => item.if.length || item.department_codes.length);
      if (allIf.length) data.all_if = allIf;
      if (idsFrom.length) data.ids_from = idsFrom;
      if (appendIdsIf.length) data.append_ids_if = appendIdsIf;
      return [String(scope.key).trim(), data];
    }));
    const abilities = Object.fromEntries((config.abilities || []).filter((ability) => String(ability.key || '').trim()).map((ability) => [
      String(ability.key).trim(),
      (ability.conditions || []).filter((rule) => String(rule.type || '').trim()).map(normalizeCondition),
    ]));
    const settings = Object.fromEntries((config.settings || []).filter((setting) => String(setting.key || '').trim()).map((setting) => [
      String(setting.key).trim(),
      normalizeSettingValue(setting),
    ]));
    const templatePermissions = Object.fromEntries((config.template_permissions || []).filter((templatePermission) => String(templatePermission.key || '').trim()).map((templatePermission) => [
      String(templatePermission.key).trim(),
      {
        view: (templatePermission.view_conditions || []).filter((rule) => String(rule.type || '').trim()).map(normalizeCondition),
        approve: (templatePermission.approve_conditions || []).filter((rule) => String(rule.type || '').trim()).map(normalizeCondition),
      },
    ]));

    if (Object.keys(scopes).length > 0) {
      moduleData.scopes = scopes;
    }

    if (Object.keys(abilities).length > 0) {
      moduleData.abilities = abilities;
    }

    if (Object.keys(settings).length > 0) {
      moduleData.settings = settings;
    }

    if (Object.keys(templatePermissions).length > 0) {
      moduleData.template_permissions = templatePermissions;
    }

    return [moduleKey, moduleData];
  }));
}

function stable(value) {
  if (Array.isArray(value)) return `[${value.map(stable).join(',')}]`;
  if (value && typeof value === 'object') return `{${Object.keys(value).sort().map((key) => `${JSON.stringify(key)}:${stable(value[key])}`).join(',')}}`;
  return JSON.stringify(value);
}

function summarizeCondition(rule) {
  if (needsValue(rule.type)) return `${rule.type}: ${rule.value || '-'}`;
  if (needsValues(rule.type)) return `${rule.type}: ${(rule.values || []).join(', ') || '-'}`;
  return rule.type || '-';
}

function summarizeSection(items, kind) {
  return (items || []).filter((item) => String(item.key || '').trim()).map((item) => ({
    key: item.key,
    text: kind === 'scope'
      ? [
          item.all_if?.length ? `all_if(${item.all_if.map(summarizeCondition).join(' | ')})` : null,
          item.ids_from?.length ? `ids_from(${item.ids_from.join(', ')})` : null,
          item.append_ids_if?.length ? `append(${item.append_ids_if.map((appendRule) => `${appendRule.if.map(summarizeCondition).join(' | ')} => ${appendRule.department_codes.join(', ')}`).join(' ; ')})` : null,
        ].filter(Boolean).join(' ; ') || 'Kosong'
      : kind === 'template_permission'
        ? [
            item.view_conditions?.length ? `view(${item.view_conditions.map(summarizeCondition).join(' | ')})` : 'view(kosong)',
            item.approve_conditions?.length ? `approve(${item.approve_conditions.map(summarizeCondition).join(' | ')})` : 'approve(kosong)',
          ].join(' ; ')
        : kind === 'setting'
          ? formatSettingValue(item.value)
          : (item.conditions || []).map(summarizeCondition).join(' | ') || 'Kosong',
  }));
}

function isChanged(moduleKey) {
  return stable(serialize()[moduleKey] || {}) !== stable(props.defaultModules?.[moduleKey] || {});
}

function describeDefaultScope(scopeKey) {
  if (!String(scopeKey || '').trim()) return 'Isi key scope untuk melihat pembanding default.';
  const item = (base.value.scopes || []).find((scope) => scope.key === scopeKey);
  return item ? summarizeSection([item], 'scope')[0].text : 'Scope ini belum ada di default.';
}

function describeDefaultAbility(abilityKey) {
  if (!String(abilityKey || '').trim()) return 'Isi key ability untuk melihat pembanding default.';
  const item = (base.value.abilities || []).find((ability) => ability.key === abilityKey);
  return item ? summarizeSection([item], 'ability')[0].text : 'Ability ini belum ada di default.';
}

function describeDefaultSetting(settingKey) {
  if (!String(settingKey || '').trim()) return 'Isi key setting untuk melihat pembanding default.';
  const item = (base.value.settings || []).find((setting) => setting.key === settingKey);
  return item ? summarizeSection([item], 'setting')[0].text : 'Setting ini belum ada di default.';
}

function describeDefaultTemplatePermission(templateKey, actionKey) {
  if (!String(templateKey || '').trim()) return 'Isi key template untuk melihat pembanding default.';
  const item = (base.value.template_permissions || []).find((templatePermission) => templatePermission.key === templateKey);
  if (!item) {
    return 'Template permission ini belum ada di default.';
  }

  const conditions = actionKey === 'approve' ? item.approve_conditions : item.view_conditions;
  return conditions.length ? conditions.map(summarizeCondition).join(' | ') : 'Kosong';
}

function humanReadableIdsFrom(idsFrom = []) {
  const values = cleanArray(idsFrom);
  if (values.length === 0) {
    return '';
  }

  const messages = [];

  if (values.includes('own_department')) {
    messages.push('User akan mendapat akses ke departemen dirinya sendiri.');
  }

  if (values.includes('managed_departments')) {
    messages.push('Manager/Supervisor akan mendapat akses ke departemen yang mereka kelola.');
  }

  if (messages.length === 0) {
    return `Akses dasar diambil dari: ${values.join(', ')}.`;
  }

  return messages.join(' ');
}

function humanReadableAppendRules(appendRules = []) {
  return (Array.isArray(appendRules) ? appendRules : [])
    .map((appendRule) => {
      const conditions = Array.isArray(appendRule?.if) ? appendRule.if : [];
      const departmentCodes = cleanArray(appendRule?.department_codes || []);

      if (departmentCodes.length === 0) {
        return '';
      }

      const managerCondition = conditions.find((rule) => String(rule?.type || '') === 'manager_in_department_codes');
      if (managerCondition) {
        const managerDepartments = cleanArray(managerCondition.values || []);
        if (managerDepartments.length > 0) {
          return `Manager ${managerDepartments.join(', ')} akan mendapat tambahan akses ke: ${departmentCodes.join(', ')}.`;
        }
      }

      const departmentCodeCondition = conditions.find((rule) => String(rule?.type || '') === 'department_code');
      if (departmentCodeCondition && String(departmentCodeCondition.value || '').trim() !== '') {
        return `User dari departemen ${String(departmentCodeCondition.value).trim()} akan mendapat tambahan akses ke: ${departmentCodes.join(', ')}.`;
      }

      const departmentNameCondition = conditions.find((rule) => String(rule?.type || '') === 'department_name_contains');
      if (departmentNameCondition && String(departmentNameCondition.value || '').trim() !== '') {
        return `User dengan nama departemen mengandung "${String(departmentNameCondition.value).trim()}" akan mendapat tambahan akses ke: ${departmentCodes.join(', ')}.`;
      }

      if (conditions.some((rule) => String(rule?.type || '') === 'admin')) {
        return `Admin akan mendapat tambahan akses ke: ${departmentCodes.join(', ')}.`;
      }

      if (conditions.some((rule) => String(rule?.type || '') === 'manager')) {
        return `Manager akan mendapat tambahan akses ke: ${departmentCodes.join(', ')}.`;
      }

      if (conditions.some((rule) => String(rule?.type || '') === 'supervisor')) {
        return `Supervisor akan mendapat tambahan akses ke: ${departmentCodes.join(', ')}.`;
      }

      return `Jika kondisi tambahan terpenuhi, akses akan ditambah ke: ${departmentCodes.join(', ')}.`;
    })
    .filter(Boolean);
}

function scopeKeyOptions(currentKey = '') {
  const keys = new Set();

  (base.value.scopes || []).forEach((scope) => {
    const key = String(scope?.key || '').trim();
    if (key) keys.add(key);
  });

  (selected.value?.scopes || []).forEach((scope) => {
    const key = String(scope?.key || '').trim();
    if (key) keys.add(key);
  });

  const current = String(currentKey || '').trim();
  if (current) {
    keys.add(current);
  }

  return Array.from(keys).sort();
}

function abilityKeyOptions(currentKey = '') {
  const keys = new Set();

  (base.value.abilities || []).forEach((ability) => {
    const key = String(ability?.key || '').trim();
    if (key) keys.add(key);
  });

  (selected.value?.abilities || []).forEach((ability) => {
    const key = String(ability?.key || '').trim();
    if (key) keys.add(key);
  });

  const current = String(currentKey || '').trim();
  if (current) {
    keys.add(current);
  }

  return Array.from(keys).sort();
}

function settingKeyOptions(currentKey = '') {
  const keys = new Set();

  (base.value.settings || []).forEach((setting) => {
    const key = String(setting?.key || '').trim();
    if (key) keys.add(key);
  });

  (selected.value?.settings || []).forEach((setting) => {
    const key = String(setting?.key || '').trim();
    if (key) keys.add(key);
  });

  const current = String(currentKey || '').trim();
  if (current) {
    keys.add(current);
  }

  return Array.from(keys).sort();
}

function templatePermissionKeyOptions(currentKey = '') {
  const keys = new Set();

  (base.value.template_permissions || []).forEach((templatePermission) => {
    const key = String(templatePermission?.key || '').trim();
    if (key) keys.add(key);
  });

  (selected.value?.template_permissions || []).forEach((templatePermission) => {
    const key = String(templatePermission?.key || '').trim();
    if (key) keys.add(key);
  });

  const current = String(currentKey || '').trim();
  if (current) {
    keys.add(current);
  }

  return Array.from(keys).sort();
}

function availableDepartmentCodeOptions(selectedCodes = []) {
  const selected = new Set(cleanArray(selectedCodes));

  return (props.departmentCodes || []).filter((department) => {
    const code = String(department?.code || '').trim();
    return code !== '' && !selected.has(code);
  });
}

function addDepartmentCode(appendRule) {
  const code = String(appendRule?.pendingDepartmentCode || '').trim();
  if (!code) {
    return;
  }

  const existing = new Set(cleanArray(appendRule.department_codes || []));
  if (!existing.has(code)) {
    appendRule.department_codes.push(code);
  }
  appendRule.pendingDepartmentCode = '';
}

function availableIdsFromOptions(selectedItems = []) {
  const selected = new Set(cleanArray(selectedItems));
  return idsFromOptions.filter((option) => !selected.has(option.value));
}

function addIdsFrom(scope) {
  const value = String(scope?.pendingIdsFrom || '').trim();
  if (!value) {
    return;
  }

  const existing = new Set(cleanArray(scope.ids_from || []));
  if (!existing.has(value)) {
    scope.ids_from.push(value);
  }
  scope.pendingIdsFrom = '';
}

function addScope() {
  if (!selected.value) return;
  const firstAvailableKey = scopeKeyOptions('').find((key) => !(selected.value?.scopes || []).some((scope) => String(scope?.key || '').trim() === key)) || '';
  selected.value.scopes.push({ key: firstAvailableKey, all_if: [], ids_from: [], append_ids_if: [], pendingIdsFrom: '' });
}

function addAbility() {
  if (!selected.value) return;
  const firstAvailableKey = abilityKeyOptions('').find((key) => !(selected.value?.abilities || []).some((ability) => String(ability?.key || '').trim() === key)) || '';
  selected.value.abilities.push({ key: firstAvailableKey, conditions: [] });
}

function addSetting() {
  if (!selected.value) return;
  const firstAvailableKey = settingKeyOptions('').find((key) => !(selected.value?.settings || []).some((setting) => String(setting?.key || '').trim() === key)) || '';
  const defaultItem = (base.value.settings || []).find((setting) => setting.key === firstAvailableKey);
  selected.value.settings.push({ key: firstAvailableKey, value: defaultItem?.value ?? '' });
}

function addTemplatePermission() {
  if (!selected.value) return;
  const firstAvailableKey = templatePermissionKeyOptions('').find((key) => !(selected.value?.template_permissions || []).some((templatePermission) => String(templatePermission?.key || '').trim() === key)) || '';
  selected.value.template_permissions.push({ key: firstAvailableKey, view_conditions: [], approve_conditions: [] });
}

function goToStep(stepNumber) {
  if (stepNumber < 1 || stepNumber > wizardSteps.length) {
    return;
  }

  if (stepNumber > 1 && !hasSelectedModule.value) {
    return;
  }

  currentStep.value = stepNumber;
}

function nextStep() {
  if (!canGoNext.value) {
    return;
  }

  currentStep.value += 1;
}

function prevStep() {
  if (currentStep.value <= 1) {
    return;
  }

  currentStep.value -= 1;
}

function stepButtonClass(stepNumber) {
  if (currentStep.value === stepNumber) {
    return 'border-indigo-500 bg-indigo-600/20 text-white';
  }

  if (stepNumber > 1 && !hasSelectedModule.value) {
    return 'border-slate-800 bg-slate-950 text-slate-500 cursor-not-allowed';
  }

  return 'border-slate-700 bg-slate-800 text-slate-300 hover:border-slate-600';
}

function resetDraft() {
  states.value = makeStates(props.modules);
  selectedModuleKey.value = Object.keys(states.value)[0] || '';
  currentStep.value = 1;
}

function saveRules() {
  router.post('/control-panel/access-rules/save', { modules: serialize() }, { preserveScroll: true });
}

async function resetOverrides() {
  const ok = await swalConfirm({
    title: 'Reset Override Access Rules?',
    text: 'Semua override yang disimpan akan dihapus dan rule kembali ke default.',
    confirmButtonText: 'Reset',
    confirmButtonColor: '#dc2626',
  });
  if (!ok) return;
  router.delete('/control-panel/access-rules/reset', { preserveScroll: true });
}

function canRollbackEntry(entry) {
  return Array.isArray(entry?.snapshots?.after_overrides);
}

function auditActionLabel(action) {
  return action === 'rollback' ? 'Rollback' : action === 'reset' ? 'Reset' : 'Save';
}

function auditActionTitle(action) {
  return action === 'rollback'
    ? 'Rollback Override'
    : action === 'reset'
      ? 'Reset Override'
      : 'Save Override';
}

function auditActionBadgeClass(action) {
  if (action === 'rollback') {
    return 'bg-amber-500/20 text-amber-300';
  }

  if (action === 'reset') {
    return 'bg-rose-500/20 text-rose-300';
  }

  return 'bg-emerald-500/20 text-emerald-300';
}

function previewModuleSnapshot(entry) {
  const overrides = entry?.snapshots?.after_overrides;

  if (!overrides || typeof overrides !== 'object') {
    return {};
  }

  const moduleKey = String(auditModuleFilter.value || '').trim();

  if (!moduleKey) {
    return overrides;
  }

  return Object.prototype.hasOwnProperty.call(overrides, moduleKey)
    ? { [moduleKey]: overrides[moduleKey] }
    : {};
}

async function previewRollback(entry) {
  if (!canRollbackEntry(entry)) {
    window.Swal.fire({
      icon: 'warning',
      title: 'Snapshot Tidak Tersedia',
      text: 'Audit lama ini belum memiliki snapshot untuk rollback.',
      confirmButtonColor: '#d97706',
    });
    return;
  }

  const snapshot = previewModuleSnapshot(entry);
  const snapshotText = JSON.stringify(snapshot, null, 2);
  const previewHtml = `
    <div style="text-align:left">
      <div style="margin-bottom:8px;font-size:12px;color:#94a3b8;">Target audit: ${entry.performed_at}</div>
      <pre style="max-height:360px;overflow:auto;background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;font-size:12px;line-height:1.4;">${escapeHtml(snapshotText)}</pre>
    </div>
  `;

  const result = await window.Swal.fire({
    icon: 'warning',
    title: 'Preview Rollback',
    html: previewHtml,
    width: 800,
    confirmButtonText: 'Lanjut Rollback',
    confirmButtonColor: '#d97706',
    showCancelButton: true,
    cancelButtonText: 'Batal',
    cancelButtonColor: '#475569',
  });

  if (!result.isConfirmed) {
    return;
  }

  rollbackAudit(entry);
}

async function rollbackAudit(entry) {
  if (!canRollbackEntry(entry)) {
    window.Swal.fire({
      icon: 'warning',
      title: 'Snapshot Tidak Tersedia',
      text: 'Audit lama ini belum memiliki snapshot untuk rollback.',
      confirmButtonColor: '#d97706',
    });
    return;
  }

  const ok = await swalConfirm({
    title: 'Rollback Access Rule?',
    text: `Access rule akan dikembalikan ke versi audit ${entry.performed_at}.`,
    confirmButtonText: 'Rollback',
    confirmButtonColor: '#d97706',
  });

  if (!ok) return;

  router.post('/control-panel/access-rules/rollback', {
    audit_id: entry.id,
  }, {
    preserveScroll: true,
  });
}

function formatAuditList(items) {
  return Array.isArray(items) && items.length > 0 ? items.join(', ') : '-';
}

function formatChangedModules(items) {
  if (!Array.isArray(items) || items.length === 0) {
    return '-';
  }

  return items.map((item) => {
    const details = [];

    if (Array.isArray(item.scopes_added) && item.scopes_added.length > 0) {
      details.push(`+scope:${item.scopes_added.join('/')}`);
    }

    if (Array.isArray(item.scopes_removed) && item.scopes_removed.length > 0) {
      details.push(`-scope:${item.scopes_removed.join('/')}`);
    }

    if (Array.isArray(item.scopes_changed) && item.scopes_changed.length > 0) {
      details.push(`~scope:${item.scopes_changed.map((rule) => rule.key).join('/')}`);
    }

    if (Array.isArray(item.abilities_added) && item.abilities_added.length > 0) {
      details.push(`+ability:${item.abilities_added.join('/')}`);
    }

    if (Array.isArray(item.abilities_removed) && item.abilities_removed.length > 0) {
      details.push(`-ability:${item.abilities_removed.join('/')}`);
    }

    if (Array.isArray(item.abilities_changed) && item.abilities_changed.length > 0) {
      details.push(`~ability:${item.abilities_changed.map((rule) => rule.key).join('/')}`);
    }

    if (Array.isArray(item.settings_added) && item.settings_added.length > 0) {
      details.push(`+setting:${item.settings_added.join('/')}`);
    }

    if (Array.isArray(item.settings_removed) && item.settings_removed.length > 0) {
      details.push(`-setting:${item.settings_removed.join('/')}`);
    }

    if (Array.isArray(item.settings_changed) && item.settings_changed.length > 0) {
      details.push(`~setting:${item.settings_changed.map((rule) => rule.key).join('/')}`);
    }

    if (Array.isArray(item.template_permissions_added) && item.template_permissions_added.length > 0) {
      details.push(`+template:${item.template_permissions_added.join('/')}`);
    }

    if (Array.isArray(item.template_permissions_removed) && item.template_permissions_removed.length > 0) {
      details.push(`-template:${item.template_permissions_removed.join('/')}`);
    }

    if (Array.isArray(item.template_permissions_changed) && item.template_permissions_changed.length > 0) {
      details.push(`~template:${item.template_permissions_changed.map((rule) => rule.key).join('/')}`);
    }

    return details.length > 0 ? `${item.module} (${details.join(', ')})` : item.module;
  }).join(' ; ');
}

function toggleAuditExpand(entryId) {
  expandedAuditIds.value = expandedAuditIds.value.includes(entryId)
    ? expandedAuditIds.value.filter((id) => id !== entryId)
    : [...expandedAuditIds.value, entryId];
}

function filteredChangedModules(summary) {
  const items = Array.isArray(summary?.changed_modules) ? summary.changed_modules : [];
  const moduleKey = String(auditModuleFilter.value || '').trim();

  return moduleKey
    ? items.filter((item) => item.module === moduleKey)
    : items;
}

function detailHeader(moduleChange) {
  const details = [];

  if (moduleChange.scopes_added?.length) details.push(`scope baru ${moduleChange.scopes_added.length}`);
  if (moduleChange.scopes_removed?.length) details.push(`scope dihapus ${moduleChange.scopes_removed.length}`);
  if (moduleChange.scopes_changed?.length) details.push(`scope diubah ${moduleChange.scopes_changed.length}`);
  if (moduleChange.abilities_added?.length) details.push(`ability baru ${moduleChange.abilities_added.length}`);
  if (moduleChange.abilities_removed?.length) details.push(`ability dihapus ${moduleChange.abilities_removed.length}`);
  if (moduleChange.abilities_changed?.length) details.push(`ability diubah ${moduleChange.abilities_changed.length}`);
  if (moduleChange.settings_added?.length) details.push(`setting baru ${moduleChange.settings_added.length}`);
  if (moduleChange.settings_removed?.length) details.push(`setting dihapus ${moduleChange.settings_removed.length}`);
  if (moduleChange.settings_changed?.length) details.push(`setting diubah ${moduleChange.settings_changed.length}`);
  if (moduleChange.template_permissions_added?.length) details.push(`template baru ${moduleChange.template_permissions_added.length}`);
  if (moduleChange.template_permissions_removed?.length) details.push(`template dihapus ${moduleChange.template_permissions_removed.length}`);
  if (moduleChange.template_permissions_changed?.length) details.push(`template diubah ${moduleChange.template_permissions_changed.length}`);

  return details.length > 0 ? details.join(' | ') : 'Perubahan struktur module';
}

function moduleChangeCount(moduleChange) {
  return (moduleChange.scopes_added?.length || 0)
    + (moduleChange.scopes_removed?.length || 0)
    + (moduleChange.scopes_changed?.length || 0)
    + (moduleChange.abilities_added?.length || 0)
    + (moduleChange.abilities_removed?.length || 0)
    + (moduleChange.abilities_changed?.length || 0)
    + (moduleChange.settings_added?.length || 0)
    + (moduleChange.settings_removed?.length || 0)
    + (moduleChange.settings_changed?.length || 0)
    + (moduleChange.template_permissions_added?.length || 0)
    + (moduleChange.template_permissions_removed?.length || 0)
    + (moduleChange.template_permissions_changed?.length || 0);
}

function formatRuleValue(value) {
  return JSON.stringify(value ?? {}, null, 2);
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}
</script>
