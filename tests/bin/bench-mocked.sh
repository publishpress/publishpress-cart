#!/usr/bin/env bash
# Run composer test:all:mocked once, sample CPU clock + temp every 2s, graph at end.
#
# From inside distrobox publishpress, at the repo root:
#   bash tests/bin/bench-mocked.sh
#   bash tests/bin/bench-mocked.sh performance
#
# Optional label is stored on the run so you can change power mode and compare.
# Override the command with BENCH_CMD='sleep 8' to test the sampler.

set -u

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

INTERVAL=2
LABEL="${1:-}"
CMD="${BENCH_CMD:-composer test:all:mocked}"
OUT_DIR="$ROOT/tmp/bench"
STAMP="$(date +%Y%m%d-%H%M%S)"

detect_label() {
    local tuned epp
    tuned="$(tuned-adm active 2>/dev/null | awk -F': ' '/active profile/{print $2; exit}')"
    epp="$(cat /sys/devices/system/cpu/cpu0/cpufreq/energy_performance_preference 2>/dev/null || true)"
    if [[ -n "$tuned" ]]; then
        echo "$tuned"
    elif [[ -n "$epp" ]]; then
        echo "$epp"
    else
        echo "run"
    fi
}

safe_label() {
    local s="${1:-run}"
    s="${s//[^A-Za-z0-9._-]/_}"
    [[ -n "$s" ]] || s="run"
    echo "$s"
}

find_tctl_input() {
    local d label
    for d in /sys/class/hwmon/hwmon*; do
        [[ -f "$d/name" ]] || continue
        [[ "$(cat "$d/name")" == k10temp ]] || continue
        for label in "$d"/temp*_label; do
            [[ -f "$label" ]] || continue
            if [[ "$(cat "$label")" == Tctl ]]; then
                echo "${label%_label}_input"
                return 0
            fi
        done
        if [[ -f "$d/temp1_input" ]]; then
            echo "$d/temp1_input"
            return 0
        fi
    done
    return 1
}

read_clocks() {
    local f hz sum=0 max=0 n=0
    for f in /sys/devices/system/cpu/cpu*/cpufreq/scaling_cur_freq; do
        [[ -r "$f" ]] || continue
        hz="$(cat "$f" 2>/dev/null)" || continue
        [[ "$hz" =~ ^[0-9]+$ ]] || continue
        sum=$((sum + hz))
        n=$((n + 1))
        if (( hz > max )); then
            max=$hz
        fi
    done
    if (( n == 0 )); then
        echo "0 0"
        return
    fi
    echo "$((sum / n / 1000)) $((max / 1000))"
}

read_temp_c() {
    local raw
    if [[ -z "$TCTL_INPUT" || ! -r "$TCTL_INPUT" ]]; then
        echo "0"
        return
    fi
    raw="$(cat "$TCTL_INPUT" 2>/dev/null || echo 0)"
    awk -v raw="$raw" 'BEGIN { printf "%.1f", raw / 1000 }'
}

if [[ -z "$LABEL" ]]; then
    LABEL="$(detect_label)"
fi
LABEL="$(safe_label "$LABEL")"

TCTL_INPUT="$(find_tctl_input || true)"
if [[ -z "$TCTL_INPUT" ]]; then
    echo "warning: k10temp Tctl not found; temp will be 0" >&2
fi

mkdir -p "$OUT_DIR"
CSV="$OUT_DIR/${STAMP}-${LABEL}.csv"
SVG="$OUT_DIR/${STAMP}-${LABEL}.svg"
INDEX="$OUT_DIR/index.tsv"

GOVERNOR="$(cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_governor 2>/dev/null || echo "?")"
EPP="$(cat /sys/devices/system/cpu/cpu0/cpufreq/energy_performance_preference 2>/dev/null || echo "?")"
TUNED="$(tuned-adm active 2>/dev/null | awk -F': ' '/active profile/{print $2; exit}')"
TUNED="${TUNED:-?}"

echo "elapsed_s,clock_avg_mhz,clock_max_mhz,temp_c" > "$CSV"

START_NS="$(date +%s%N)"
composer_pid=""
sampler_stop=0

elapsed_s() {
    local now
    now="$(date +%s%N)"
    awk -v start="$START_NS" -v now="$now" 'BEGIN { printf "%.1f", (now - start) / 1e9 }'
}

sample() {
    local elapsed clocks avg max temp
    elapsed="$(elapsed_s)"
    clocks="$(read_clocks)"
    avg="${clocks% *}"
    max="${clocks#* }"
    temp="$(read_temp_c)"
    echo "$elapsed,$avg,$max,$temp" >> "$CSV"
    printf 'bench  %6ss  clock %4s/%4s MHz  temp %5s°C\n' "$elapsed" "$avg" "$max" "$temp" >&2
}

stop_composer() {
    sampler_stop=1
    if [[ -n "$composer_pid" ]] && kill -0 "$composer_pid" 2>/dev/null; then
        kill "$composer_pid" 2>/dev/null || true
        wait "$composer_pid" 2>/dev/null || true
    fi
}

trap stop_composer INT TERM

echo "bench  label=$LABEL  tuned=$TUNED  governor=$GOVERNOR  epp=$EPP"
echo "bench  command: $CMD"
echo "bench  samples → $CSV"
echo

sample

set +e
# shellcheck disable=SC2086
eval "$CMD" &
composer_pid=$!

while kill -0 "$composer_pid" 2>/dev/null && [[ "$sampler_stop" -eq 0 ]]; do
    sleep "$INTERVAL"
    if kill -0 "$composer_pid" 2>/dev/null; then
        sample
    fi
done

wait "$composer_pid"
exitcode=$?
composer_pid=""
trap - INT TERM
set -e

sample
echo

TOTAL="$(elapsed_s)"

summarize_csv() {
    awk -F, '
        NR == 1 { next }
        NF < 4 { next }
        {
            n++
            e = $1 + 0
            a = $2 + 0
            m = $3 + 0
            t = $4 + 0
            if (n == 1 || a < amin) amin = a
            if (n == 1 || a > amax) amax = a
            if (n == 1 || m < mmin) mmin = m
            if (n == 1 || m > mmax) mmax = m
            if (n == 1 || t < tmin) tmin = t
            if (n == 1 || t > tmax) tmax = t
            asum += a; msum += m; tsum += t
            last = e
        }
        END {
            if (n < 1) {
                print "0 0 0 0 0 0 0 0 0 0 0"
                exit
            }
            printf "%d %.1f %.0f %.0f %.0f %.0f %.0f %.0f %.1f %.1f %.1f\n",
                n, last, amin, asum / n, amax, mmin, msum / n, mmax, tmin, tsum / n, tmax
        }
    ' "$1"
}

read -r N_SAMPLES LAST AMIN AAVG AMAX MMIN MAVG MMAX TMIN TAVG TMAX <<<"$(summarize_csv "$CSV")"

{
    printf '%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\n' \
        "$STAMP" "$LABEL" "$TOTAL" "$AAVG" "$AMAX" "$TAVG" "$TMAX" "$exitcode" "$CMD"
} >> "$INDEX"

plot_ascii() {
    local file="$1" col="$2" title="$3" unit="$4"
    awk -F, -v col="$col" -v title="$title" -v unit="$unit" '
        NR == 1 { next }
        NF < 4 { next }
        {
            x[++n] = $1 + 0
            y[n] = $col + 0
            if (n == 1 || y[n] < ymin) ymin = y[n]
            if (n == 1 || y[n] > ymax) ymax = y[n]
            if (n == 1 || x[n] < xmin) xmin = x[n]
            if (n == 1 || x[n] > xmax) xmax = x[n]
        }
        END {
            width = 64
            height = 12
            if (n < 1) {
                print title ": no samples"
                exit
            }
            rawmin = ymin
            rawmax = ymax
            if (ymax <= ymin) {
                ymin = ymin - 1
                ymax = ymax + 1
            }
            pad = (ymax - ymin) * 0.05
            ymin -= pad
            ymax += pad
            span = xmax - xmin
            if (span <= 0) span = 1
            print ""
            printf "  %s   n=%d  min=%.1f  max=%.1f %s\n", title, n, rawmin, rawmax, unit
            for (row = height; row >= 1; row--) {
                thresh = ymin + (ymax - ymin) * (row - 0.5) / height
                if (row == height)
                    printf "  %6.0f |", ymax
                else if (row == 1)
                    printf "  %6.0f |", ymin
                else
                    printf "         |"
                for (c = 1; c <= width; c++) {
                    xt = xmin + span * (c - 0.5) / width
                    # linear interpolate y at xt
                    if (xt <= x[1]) {
                        yt = y[1]
                    } else if (xt >= x[n]) {
                        yt = y[n]
                    } else {
                        j = 1
                        while (j < n && x[j + 1] < xt) j++
                        dx = x[j + 1] - x[j]
                        if (dx == 0) yt = y[j]
                        else yt = y[j] + (y[j + 1] - y[j]) * (xt - x[j]) / dx
                    }
                    if (yt >= thresh) printf "█"
                    else printf " "
                }
                print ""
            }
            printf "         +"
            for (c = 1; c <= width; c++) printf "-"
            print ""
            printf "         %-7s", sprintf("%.0fs", xmin)
            sp = width - 14
            if (sp < 1) sp = 1
            for (i = 1; i <= sp; i++) printf " "
            printf "%7s\n", sprintf("%.0fs", xmax)
        }
    ' "$file"
}

write_svg() {
    awk -F, -v label="$LABEL" -v total="$TOTAL" -v stamp="$STAMP" '
        NR == 1 { next }
        NF < 4 { next }
        {
            n++
            x[n] = $1 + 0
            a[n] = $2 + 0
            m[n] = $3 + 0
            t[n] = $4 + 0
            if (n == 1 || a[n] < amin) amin = a[n]
            if (n == 1 || a[n] > amax) amax = a[n]
            if (n == 1 || m[n] < mmin) mmin = m[n]
            if (n == 1 || m[n] > mmax) mmax = m[n]
            if (n == 1 || t[n] < tmin) tmin = t[n]
            if (n == 1 || t[n] > tmax) tmax = t[n]
            if (n == 1 || x[n] < xmin) xmin = x[n]
            if (n == 1 || x[n] > xmax) xmax = x[n]
        }
        function sx(v,   span) {
            span = xmax - xmin
            if (span <= 0) span = 1
            return 60 + (v - xmin) / span * 700
        }
        function sy(v, ymin, ymax, top,   span) {
            span = ymax - ymin
            if (span <= 0) span = 1
            return top + 160 - (v - ymin) / span * 140
        }
        function poly(arr, ymin, ymax, top,   i, s) {
            s = ""
            for (i = 1; i <= n; i++) {
                if (i > 1) s = s " "
                s = s sprintf("%.1f,%.1f", sx(x[i]), sy(arr[i], ymin, ymax, top))
            }
            return s
        }
        END {
            if (n < 1) exit
            if (amax <= amin) { amin -= 1; amax += 1 }
            if (tmax <= tmin) { tmin -= 1; tmax += 1 }
            print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
            print "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"800\" height=\"460\" viewBox=\"0 0 800 460\">"
            print "<rect width=\"800\" height=\"460\" fill=\"#111\"/>"
            printf "<text x=\"20\" y=\"28\" fill=\"#eee\" font-family=\"sans-serif\" font-size=\"16\">%s  %ss  %s</text>\n", stamp, total, label
            print "<text x=\"20\" y=\"52\" fill=\"#8cf\" font-family=\"sans-serif\" font-size=\"13\">CPU clock avg (MHz)</text>"
            printf "<polyline fill=\"none\" stroke=\"#6cf\" stroke-width=\"2\" points=\"%s\"/>\n", poly(a, amin, amax, 60)
            printf "<text x=\"20\" y=\"78\" fill=\"#888\" font-family=\"sans-serif\" font-size=\"11\">%.0f – %.0f MHz</text>\n", amin, amax
            print "<text x=\"20\" y=\"250\" fill=\"#fa6\" font-family=\"sans-serif\" font-size=\"13\">CPU Tctl (°C)</text>"
            printf "<polyline fill=\"none\" stroke=\"#fa6\" stroke-width=\"2\" points=\"%s\"/>\n", poly(t, tmin, tmax, 260)
            printf "<text x=\"20\" y=\"276\" fill=\"#888\" font-family=\"sans-serif\" font-size=\"11\">%.1f – %.1f °C</text>\n", tmin, tmax
            print "</svg>"
        }
    ' "$CSV" > "$SVG"
}

plot_ascii "$CSV" 2 "CPU clock avg (MHz)" "MHz"
plot_ascii "$CSV" 3 "CPU clock max (MHz)" "MHz"
plot_ascii "$CSV" 4 "CPU temp Tctl (°C)" "°C"
write_svg

echo
echo "This run"
echo "  label     $LABEL"
echo "  total     ${TOTAL}s   exit $exitcode"
echo "  samples   $N_SAMPLES every ${INTERVAL}s"
echo "  clock avg $AAVG MHz  (min $AMIN  max $AMAX)"
echo "  clock max $MAVG MHz avg of per-sample peaks  (min $MMIN  max $MMAX)"
echo "  temp      ${TAVG}°C  (min $TMIN  max $TMAX)"
echo "  tuned     $TUNED"
echo "  governor  $GOVERNOR"
echo "  epp       $EPP"
echo "  csv       $CSV"
echo "  svg       $SVG"

echo
echo "All runs (tmp/bench/index.tsv)"
printf "  %-17s %-18s %10s %10s %10s %8s %8s %6s\n" stamp label total_s clk_avg clk_max t_avg t_max exit
if [[ -f "$INDEX" ]]; then
    awk -F'\t' '{
        printf "  %-17s %-18s %9ss %8s %8s %7s %7s %6s\n", $1, $2, $3, $4, $5, $6, $7, $8
    }' "$INDEX"
fi

echo
echo "Change power mode, then run again with a label:"
echo "  bash tests/bin/bench-mocked.sh performance"

exit "$exitcode"
