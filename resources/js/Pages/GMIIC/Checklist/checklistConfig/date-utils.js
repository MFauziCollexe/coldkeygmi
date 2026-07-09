export function formatDateDisplay(date = new Date()) {
    return new Intl.DateTimeFormat("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    }).format(date);
}

export function formatDateInputDisplay(dateValue) {
    if (!String(dateValue || "").trim()) {
        return "-";
    }

    const [year, month, day] = String(dateValue).split("-").map(Number);
    if (!year || !month || !day) {
        return "-";
    }

    return formatDateDisplay(new Date(year, month - 1, day));
}

export function formatShortDateDisplay(date = new Date()) {
    return new Intl.DateTimeFormat("en-GB", {
        day: "2-digit",
        month: "short",
    }).format(date);
}

export function formatDayMonthDisplay(date = new Date()) {
    return `${date.getDate()}/${date.getMonth() + 1}`;
}

export function formatDateTimeDisplay(date = new Date()) {
    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
}

export function formatMonthYearDisplay(periodValue) {
    const [year, month] = String(periodValue || "").split("-");
    if (!year || !month) {
        return "-";
    }

    const date = new Date(Number(year), Number(month) - 1, 1);
    return new Intl.DateTimeFormat("id-ID", {
        month: "long",
        year: "numeric",
    }).format(date);
}

export function toPeriodValue(date = new Date()) {
    const month = `${date.getMonth() + 1}`.padStart(2, "0");
    return `${date.getFullYear()}-${month}`;
}

export function toDateInputValue(date = new Date()) {
    const month = `${date.getMonth() + 1}`.padStart(2, "0");
    const day = `${date.getDate()}`.padStart(2, "0");
    return `${date.getFullYear()}-${month}-${day}`;
}

function getIsoWeekNumber(date) {
    const target = new Date(
        Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()),
    );
    const dayNumber = target.getUTCDay() || 7;
    target.setUTCDate(target.getUTCDate() + 4 - dayNumber);
    const yearStart = new Date(Date.UTC(target.getUTCFullYear(), 0, 1));
    return Math.ceil(((target - yearStart) / 86400000 + 1) / 7);
}

export function toWeekInputValue(date = new Date()) {
    const year = date.getFullYear();
    const week = `${getIsoWeekNumber(date)}`.padStart(2, "0");
    return `${year}-W${week}`;
}

export function formatWeekDisplay(weekValue) {
    const match = String(weekValue || "").match(/^(\d{4})-W(\d{2})$/);
    if (!match) {
        return "-";
    }

    const [, year, week] = match;
    return `Minggu ${Number(week)}, ${year}`;
}

export function getDaysInPeriod(periodValue) {
    const [year, month] = String(periodValue || "").split("-");
    const safeYear = Number(year);
    const safeMonth = Number(month);

    if (!safeYear || !safeMonth) {
        return [];
    }

    const lastDay = new Date(safeYear, safeMonth, 0).getDate();

    return Array.from({ length: lastDay }, (_, index) => {
        const day = index + 1;
        const date = new Date(safeYear, safeMonth - 1, day);
        const isSunday = date.getDay() === 0;

        return {
            day,
            date: `${periodValue}-${String(day).padStart(2, "0")}`,
            key: `${periodValue}-${day}`,
            isSunday,
        };
    });
}
