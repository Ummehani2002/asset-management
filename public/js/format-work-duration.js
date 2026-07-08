function formatWorkDuration(hours) {
    const value = parseFloat(hours);
    if (!value || value <= 0) {
        return '0 min';
    }

    const totalMinutes = Math.round(value * 60);
    const hrs = Math.floor(totalMinutes / 60);
    const mins = totalMinutes % 60;

    if (hrs === 0) {
        return mins + ' min';
    }

    if (mins === 0) {
        return hrs === 1 ? '1 hr' : hrs + ' hrs';
    }

    const hrPart = hrs === 1 ? '1 hr' : hrs + ' hrs';
    return hrPart + ' ' + mins + ' min';
}
