import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { LookupChips } from '@/components/lookup-chips';
import { RecordForm, RecordFormField } from '@/components/record-form';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { LookupKind, LookupOption } from '@/lib/lookups';
import { index as indexRoute, store as storeRoute } from '@/routes/promotions';
import type { PromotionTypeOption } from '@/types/pos';
import { APPLY_DISCOUNT_TO } from '@/types/pos';

type Props = {
    types: PromotionTypeOption[];
    applyTo: string[];
    voucherSources: string[];
};

type Time = { key: number; start: string; end: string };

type FormData = {
    type: number;
    masterCode: string;
    branches: LookupOption[];
    notes: string;
    promotionCode: string;
    description: string;
    days: number[];
    startAt: string;
    endAt: string;
    allCategories: boolean;
    applyDiscountTo: number;
    scope: LookupOption[];
    usedForLoyalty: '' | 'yes' | 'no';
    paymentMethodName: string;
    discount: number | null;
    authorizationNeeded: boolean;
    minSalesPrice: number | null;
    maxSalesPrice: number | null;
    maxDiscount: number | null;
    applyTo: string;
    employeeGroups: string;
    includePackageContent: boolean;
    includeMenuExtra: boolean;
    times: Time[];
    showOnEso: boolean;
    maxUsage: number | null;
    maxUsageTotal: number | null;
    visitPurposeIds: string;
    selfOrderPaymentMethodCodes: string;
    bankIdentificationNumbers: string;
    applications: string[];
    voucherSource: string;
    prefix: string;
};

type NumberField =
    | 'discount'
    | 'minSalesPrice'
    | 'maxSalesPrice'
    | 'maxDiscount'
    | 'maxUsage'
    | 'maxUsageTotal';

type TextField =
    | 'notes'
    | 'promotionCode'
    | 'description'
    | 'masterCode'
    | 'paymentMethodName'
    | 'prefix'
    | 'employeeGroups'
    | 'visitPurposeIds'
    | 'selfOrderPaymentMethodCodes'
    | 'bankIdentificationNumbers';

/** PromotionType::isPercentage(). */
const PERCENTAGE_TYPES = [1, 10, 5];

const DAYS = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday',
];

const SCOPES: { id: number; label: string; kind: LookupKind }[] = [
    {
        id: APPLY_DISCOUNT_TO.menuCategory,
        label: 'Menu categories',
        kind: 'menu-categories',
    },
    {
        id: APPLY_DISCOUNT_TO.menuCategoryDetail,
        label: 'Category details',
        kind: 'menu-category-details',
    },
    { id: APPLY_DISCOUNT_TO.menu, label: 'Menus', kind: 'pos-menus' },
];

const selectClass =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

let nextKey = 0;

function emptyTime(): Time {
    nextKey += 1;

    return { key: nextKey, start: '', end: '' };
}

function numberOrNull(value: string): number | null {
    return value === '' ? null : Number(value);
}

/** "a, b ,c" as ["a", "b", "c"]. */
function list(value: string): string[] {
    return value
        .split(',')
        .map((part) => part.trim())
        .filter((part) => part !== '');
}

/** A datetime-local value as the ERP's "YYYY-MM-DD HH:MM:SS". */
function erpDateTime(value: string): string {
    if (value === '') {
        return '';
    }

    const text = value.replace('T', ' ');

    return text.length === 16 ? `${text}:00` : text;
}

/** A time input value as "HH:MM:SS". */
function erpTime(value: string): string {
    return value.length === 5 ? `${value}:00` : value;
}

/** The common fields, then the chosen type's own -- nothing else. */
function payload(data: FormData, type: PromotionTypeOption | undefined) {
    const own: Record<string, unknown> = {
        discount: data.discount,
        authorizationNeeded: data.authorizationNeeded,
        minSalesPrice: data.minSalesPrice,
        maxSalesPrice: data.maxSalesPrice,
        maxDiscount: data.maxDiscount,
        applyTo: data.applyTo === '' ? null : data.applyTo,
        employeeGroups: list(data.employeeGroups),
        includePackageContent: data.includePackageContent,
        includeMenuExtra: data.includeMenuExtra,
        times: data.times.map((time) => ({
            start: erpTime(time.start),
            end: erpTime(time.end),
        })),
        showOnEso: data.showOnEso,
        maxUsage: data.maxUsage,
        maxUsageTotal: data.maxUsageTotal,
        visitPurposeIds: list(data.visitPurposeIds).map(Number),
        selfOrderPaymentMethodCodes: list(data.selfOrderPaymentMethodCodes),
        bankIdentificationNumbers: list(data.bankIdentificationNumbers),
        applications: data.applications,
        voucherSource: data.voucherSource,
        prefix: data.prefix,
    };
    const scopeIds = data.scope.map((option) => Number(option.value));

    return {
        type: data.type,
        masterCode: data.masterCode,
        branchCodes: data.branches.map((branch) => branch.code ?? branch.value),
        notes: data.notes,
        promotionCode: data.promotionCode,
        description: data.description,
        days: [...data.days].sort((a, b) => a - b),
        startAt: erpDateTime(data.startAt),
        endAt: erpDateTime(data.endAt),
        allCategories: data.allCategories,
        applyDiscountTo: data.allCategories ? null : data.applyDiscountTo,
        menuCategoryIds:
            !data.allCategories &&
            data.applyDiscountTo === APPLY_DISCOUNT_TO.menuCategory
                ? scopeIds
                : [],
        menuCategoryDetailIds:
            !data.allCategories &&
            data.applyDiscountTo === APPLY_DISCOUNT_TO.menuCategoryDetail
                ? scopeIds
                : [],
        menuIds:
            !data.allCategories &&
            data.applyDiscountTo === APPLY_DISCOUNT_TO.menu
                ? scopeIds
                : [],
        usedForLoyalty:
            data.usedForLoyalty === '' ? null : data.usedForLoyalty === 'yes',
        paymentMethodName: data.paymentMethodName,
        ...Object.fromEntries(
            (type?.fields ?? []).map((field) => [field, own[field]]),
        ),
    };
}

function Check({
    id,
    label,
    checked,
    onChange,
}: {
    id: string;
    label: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
}) {
    return (
        <div className="flex items-center gap-2">
            <input
                id={id}
                type="checkbox"
                className="size-4"
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
            />
            <Label htmlFor={id}>{label}</Label>
        </div>
    );
}

/**
 * Create a POS promotion in ESB OMS ([ESB-050]): five documented types on
 * one path. The type picks the fields shown and sent. Codes the ERP has no
 * list for (ESB Order payment methods, visit purposes, employee groups,
 * bank identification numbers) are typed; the ERP refuses an unknown one.
 */
export default function PromotionForm({
    types,
    applyTo,
    voucherSources,
}: Props) {
    const form = useForm<FormData>({
        type: types[0]?.id ?? 1,
        masterCode: '',
        branches: [],
        notes: '',
        promotionCode: '',
        description: '',
        days: [],
        startAt: '',
        endAt: '',
        allCategories: true,
        applyDiscountTo: APPLY_DISCOUNT_TO.menuCategory,
        scope: [],
        usedForLoyalty: '',
        paymentMethodName: '',
        discount: null,
        authorizationNeeded: false,
        minSalesPrice: null,
        maxSalesPrice: null,
        maxDiscount: null,
        applyTo: '',
        employeeGroups: '',
        includePackageContent: false,
        includeMenuExtra: false,
        times: [],
        showOnEso: false,
        maxUsage: null,
        maxUsageTotal: null,
        visitPurposeIds: '',
        selfOrderPaymentMethodCodes: '',
        bankIdentificationNumbers: '',
        applications: ['pos'],
        voucherSource: voucherSources[0] ?? 'ESB',
        prefix: '',
    });
    const errors = form.errors as Record<string, string | undefined>;
    const { data } = form;
    const type = types.find((option) => option.id === data.type);
    const has = (field: string) => type?.fields.includes(field) ?? false;
    const freeItem = data.type === 4;
    const forEso = !freeItem || data.applications.includes('eso');
    const scopeKind =
        SCOPES.find((s) => s.id === data.applyDiscountTo) ?? SCOPES[0];

    function submit() {
        form.transform((current) => payload(current, type));
        form.post(storeRoute().url);
    }

    function numberField(field: NumberField, label: string): ReactNode {
        return (
            <RecordFormField id={field} label={label} error={errors[field]}>
                <Input
                    id={field}
                    type="number"
                    inputMode="decimal"
                    min={0}
                    step="0.0001"
                    value={data[field] ?? ''}
                    onChange={(event) =>
                        form.setData(field, numberOrNull(event.target.value))
                    }
                />
            </RecordFormField>
        );
    }

    function textField(
        field: TextField,
        label: string,
        maxLength?: number,
    ): ReactNode {
        return (
            <RecordFormField id={field} label={label} error={errors[field]}>
                <Input
                    id={field}
                    maxLength={maxLength}
                    value={data[field]}
                    onChange={(event) =>
                        form.setData(field, event.target.value)
                    }
                />
            </RecordFormField>
        );
    }

    return (
        <>
            <Head title="New promotion" />

            <RecordForm
                title="New promotion"
                description="Saved in ESB OMS straight away. Promotions cannot be edited from the gateway yet."
                back={{ href: indexRoute().url, label: 'Promotions' }}
                cancelHref={indexRoute().url}
                submitLabel="Create promotion"
                onSubmit={submit}
                processing={form.processing}
                isDirty={form.isDirty}
                formError={errors.form}
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <RecordFormField id="type" label="Type" error={errors.type}>
                        <select
                            id="type"
                            className={selectClass}
                            value={data.type}
                            onChange={(event) =>
                                form.setData('type', Number(event.target.value))
                            }
                        >
                            {types.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.name}
                                </option>
                            ))}
                        </select>
                    </RecordFormField>
                    <div className="grid gap-2">
                        <LookupChips
                            id="branches"
                            label="Branches"
                            kind="branches"
                            value={data.branches}
                            onChange={(branches) =>
                                form.setData('branches', branches)
                            }
                        />
                        <InputError message={errors.branchCodes} />
                    </div>
                    {textField('notes', 'Notes', 100)}
                    {textField('promotionCode', 'Promotion code', 20)}
                    {textField('description', 'Description (optional)', 100)}
                    {textField('masterCode', 'Master code (optional)', 50)}
                    <RecordFormField
                        id="startAt"
                        label="Starts"
                        error={errors.startAt}
                    >
                        <Input
                            id="startAt"
                            type="datetime-local"
                            step={1}
                            value={data.startAt}
                            onChange={(event) =>
                                form.setData('startAt', event.target.value)
                            }
                        />
                    </RecordFormField>
                    <RecordFormField
                        id="endAt"
                        label="Ends"
                        error={errors.endAt}
                    >
                        <Input
                            id="endAt"
                            type="datetime-local"
                            step={1}
                            value={data.endAt}
                            onChange={(event) =>
                                form.setData('endAt', event.target.value)
                            }
                        />
                    </RecordFormField>
                    <fieldset className="grid gap-2 sm:col-span-2">
                        <legend className="text-sm font-medium">Days</legend>
                        <div className="flex flex-wrap gap-4">
                            {DAYS.map((day, index) => (
                                <Check
                                    key={day}
                                    id={`day-${index + 1}`}
                                    label={day}
                                    checked={data.days.includes(index + 1)}
                                    onChange={(checked) =>
                                        form.setData(
                                            'days',
                                            checked
                                                ? [...data.days, index + 1]
                                                : data.days.filter(
                                                      (d) => d !== index + 1,
                                                  ),
                                        )
                                    }
                                />
                            ))}
                        </div>
                        <InputError message={errors.days ?? errors['days.0']} />
                    </fieldset>
                    <div className="grid gap-3 sm:col-span-2">
                        <Check
                            id="allCategories"
                            label="All menu categories"
                            checked={data.allCategories}
                            onChange={(checked) =>
                                form.setData('allCategories', checked)
                            }
                        />
                        {!data.allCategories && (
                            <div className="grid gap-4 sm:grid-cols-2">
                                <RecordFormField
                                    id="applyDiscountTo"
                                    label="Apply to"
                                    error={errors.applyDiscountTo}
                                >
                                    <select
                                        id="applyDiscountTo"
                                        className={selectClass}
                                        value={data.applyDiscountTo}
                                        onChange={(event) =>
                                            // Picks of another kind no longer apply.
                                            form.setData((current) => ({
                                                ...current,
                                                applyDiscountTo: Number(
                                                    event.target.value,
                                                ),
                                                scope: [],
                                            }))
                                        }
                                    >
                                        {SCOPES.map((option) => (
                                            <option
                                                key={option.id}
                                                value={option.id}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </RecordFormField>
                                <div className="grid gap-2">
                                    <LookupChips
                                        key={scopeKind.kind}
                                        id="scope"
                                        label={scopeKind.label}
                                        kind={scopeKind.kind}
                                        value={data.scope}
                                        onChange={(scope) =>
                                            form.setData('scope', scope)
                                        }
                                    />
                                    <InputError
                                        message={
                                            errors.menuCategoryIds ??
                                            errors.menuCategoryDetailIds ??
                                            errors.menuIds
                                        }
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                    <RecordFormField
                        id="usedForLoyalty"
                        label="Used for loyalty"
                        error={errors.usedForLoyalty}
                    >
                        <select
                            id="usedForLoyalty"
                            className={selectClass}
                            value={data.usedForLoyalty}
                            onChange={(event) =>
                                form.setData(
                                    'usedForLoyalty',
                                    event.target
                                        .value as FormData['usedForLoyalty'],
                                )
                            }
                        >
                            <option value="">ERP default (No)</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </RecordFormField>
                    {textField(
                        'paymentMethodName',
                        'POS payment method name (optional)',
                        50,
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    {has('discount') &&
                        numberField(
                            'discount',
                            PERCENTAGE_TYPES.includes(data.type)
                                ? 'Discount (%)'
                                : 'Discount (Rp)',
                        )}
                    {has('minSalesPrice') &&
                        numberField('minSalesPrice', 'Minimum subtotal')}
                    {has('maxSalesPrice') &&
                        numberField('maxSalesPrice', 'Max sales price')}
                    {has('maxDiscount') &&
                        numberField('maxDiscount', 'Maximum discount')}
                    {has('applications') && (
                        <fieldset className="grid gap-2">
                            <legend className="text-sm font-medium">
                                Applies in
                            </legend>
                            <div className="flex gap-4">
                                {[
                                    ['pos', 'POS'],
                                    ['eso', 'ESB Order'],
                                ].map(([value, label]) => (
                                    <Check
                                        key={value}
                                        id={`application-${value}`}
                                        label={label}
                                        checked={data.applications.includes(
                                            value,
                                        )}
                                        onChange={(checked) =>
                                            form.setData(
                                                'applications',
                                                checked
                                                    ? [
                                                          ...data.applications,
                                                          value,
                                                      ]
                                                    : data.applications.filter(
                                                          (a) => a !== value,
                                                      ),
                                            )
                                        }
                                    />
                                ))}
                            </div>
                            <InputError
                                message={
                                    errors.applications ??
                                    errors['applications.0']
                                }
                            />
                        </fieldset>
                    )}
                    {has('voucherSource') && (
                        <RecordFormField
                            id="voucherSource"
                            label="Voucher source"
                            error={errors.voucherSource}
                        >
                            <select
                                id="voucherSource"
                                className={selectClass}
                                value={data.voucherSource}
                                onChange={(event) =>
                                    form.setData(
                                        'voucherSource',
                                        event.target.value,
                                    )
                                }
                            >
                                {voucherSources.map((source) => (
                                    <option key={source} value={source}>
                                        {source}
                                    </option>
                                ))}
                            </select>
                        </RecordFormField>
                    )}
                    {has('prefix') &&
                        data.voucherSource === 'Giftee' &&
                        textField('prefix', 'Voucher prefix', 5)}
                    {has('maxUsage') &&
                        forEso &&
                        numberField('maxUsage', 'Max uses per customer')}
                    {has('maxUsageTotal') &&
                        forEso &&
                        numberField('maxUsageTotal', 'Max uses in total')}
                    {has('selfOrderPaymentMethodCodes') &&
                        forEso &&
                        textField(
                            'selfOrderPaymentMethodCodes',
                            'ESB Order payment method codes',
                        )}
                    {has('bankIdentificationNumbers') &&
                        textField(
                            'bankIdentificationNumbers',
                            'Bank identification numbers (optional)',
                        )}
                    {has('visitPurposeIds') &&
                        textField(
                            'visitPurposeIds',
                            'Visit purpose IDs (optional)',
                        )}
                    {has('applyTo') && (
                        <RecordFormField
                            id="applyTo"
                            label="Applies to customers"
                            error={errors.applyTo}
                        >
                            <select
                                id="applyTo"
                                className={selectClass}
                                value={data.applyTo}
                                onChange={(event) =>
                                    form.setData('applyTo', event.target.value)
                                }
                            >
                                <option value="">
                                    ERP default (All Transaction)
                                </option>
                                {applyTo.map((option) => (
                                    <option key={option} value={option}>
                                        {option}
                                    </option>
                                ))}
                            </select>
                        </RecordFormField>
                    )}
                    {has('employeeGroups') &&
                        textField(
                            'employeeGroups',
                            'Employee groups (optional)',
                        )}
                    {has('showOnEso') && (
                        <Check
                            id="showOnEso"
                            label="Show on ESB Order"
                            checked={data.showOnEso}
                            onChange={(checked) =>
                                form.setData('showOnEso', checked)
                            }
                        />
                    )}
                    {has('authorizationNeeded') && (
                        <Check
                            id="authorizationNeeded"
                            label="Needs authorization"
                            checked={data.authorizationNeeded}
                            onChange={(checked) =>
                                form.setData('authorizationNeeded', checked)
                            }
                        />
                    )}
                    {has('includePackageContent') && (
                        <Check
                            id="includePackageContent"
                            label="Include package content"
                            checked={data.includePackageContent}
                            onChange={(checked) =>
                                form.setData('includePackageContent', checked)
                            }
                        />
                    )}
                    {has('includeMenuExtra') && (
                        <Check
                            id="includeMenuExtra"
                            label="Include menu extra"
                            checked={data.includeMenuExtra}
                            onChange={(checked) =>
                                form.setData('includeMenuExtra', checked)
                            }
                        />
                    )}
                </div>

                {has('times') && (
                    <fieldset className="grid gap-2">
                        <legend className="text-sm font-medium">
                            Time windows (optional; none means all day)
                        </legend>
                        {data.times.map((time, index) => (
                            <div
                                key={time.key}
                                className="flex flex-wrap items-end gap-2"
                            >
                                {(['start', 'end'] as const).map((edge) => (
                                    <div key={edge} className="grid gap-1.5">
                                        <Label
                                            htmlFor={`time-${time.key}-${edge}`}
                                        >
                                            {`${edge === 'start' ? 'From' : 'To'}, window ${index + 1}`}
                                        </Label>
                                        <Input
                                            id={`time-${time.key}-${edge}`}
                                            type="time"
                                            step={1}
                                            value={time[edge]}
                                            onChange={(event) =>
                                                form.setData(
                                                    'times',
                                                    data.times.map((t) =>
                                                        t.key === time.key
                                                            ? {
                                                                  ...t,
                                                                  [edge]: event
                                                                      .target
                                                                      .value,
                                                              }
                                                            : t,
                                                    ),
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors[`times.${index}.${edge}`]
                                            }
                                        />
                                    </div>
                                ))}
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`Remove window ${index + 1}`}
                                    onClick={() =>
                                        form.setData(
                                            'times',
                                            data.times.filter(
                                                (t) => t.key !== time.key,
                                            ),
                                        )
                                    }
                                >
                                    <Trash2 className="size-4" aria-hidden />
                                </Button>
                            </div>
                        ))}
                        <div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    form.setData('times', [
                                        ...data.times,
                                        emptyTime(),
                                    ])
                                }
                            >
                                <Plus className="size-4" aria-hidden />
                                Add time window
                            </Button>
                        </div>
                    </fieldset>
                )}
            </RecordForm>
        </>
    );
}
