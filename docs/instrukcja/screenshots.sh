#!/usr/bin/env bash
# Zrzuty ekranu do instrukcji (docs/instrukcja/img/), tylko z LOKALNEGO Dockera.
#
#   ./docs/instrukcja/screenshots.sh                 wszystkie zrzuty
#   ./docs/instrukcja/screenshots.sh panel zestaw    tylko wybrane
#
# UWAGA: czyści lokalną bazę i wgrywa dane przykładowe (ManualDemoSeeder),
# bo zrzuty pokazują konkretne przykłady opisane w instrukcji.
set -euo pipefail
cd "$(dirname "$0")/../.."

IMG=docs/instrukcja/img
RAW=$(mktemp -d)
trap 'rm -rf "$RAW"' EXIT
chmod 777 "$RAW"
status=0

./art migrate:fresh --seed --seeder=ManualDemoSeeder --force >/dev/null

# Obraz dla udawanej kamery: naklejka Craty na ciemnym tle. Kod tylko wygląda
# jak QR (losowe moduły), żeby skaner go nie odczytał i nie przeszedł od razu
# na kartę przedmiotu w trakcie robienia zrzutu.
CAM="$RAW/camera"
mkdir -p "$CAM"
python3 - "$CAM/qr.pbm" <<'PY'
import random, sys
random.seed(7)
n = 25
m = [[random.random() < 0.5 for _ in range(n)] for _ in range(n)]
for oy, ox in [(0, 0), (0, n - 7), (n - 7, 0)]:
    for y in range(-1, 8):
        for x in range(-1, 8):
            if 0 <= oy + y < n and 0 <= ox + x < n:
                ring = max(abs(y - 3), abs(x - 3))
                m[oy + y][ox + x] = ring in (0, 1, 3)
open(sys.argv[1], "w").write(f"P1\n{n} {n}\n" + "\n".join(" ".join("1" if v else "0" for v in row) for row in m))
PY
magick "$CAM/qr.pbm" -filter point -resize 150x150 -bordercolor white -border 10 "$CAM/qr.png"
magick -size 380x200 xc:white "$CAM/qr.png" -geometry +18+15 -composite \
    -font DejaVu-Sans-Bold -pointsize 19 -fill '#111827' -annotate +200+70 'Wkrętarka' -annotate +200+96 'Makita DDF482' \
    -font DejaVu-Sans -pointsize 14 -fill '#4b5563' -annotate +200+128 'ELN-M1HALAR3P2' -annotate +200+148 '2026-00006' \
    -background none -rotate -5 "$CAM/label.png"
magick -size 640x480 gradient:'#5b5148'-'#2b2622' -attenuate 0.35 +noise Gaussian \
    "$CAM/label.png" -gravity center -geometry +0+10 -composite -blur 0x0.6 -quality 90 "JPEG:$CAM/camera.mjpeg"

docker run --rm --network host --ipc host -e BASE=http://localhost:8000 -e OUT=/out -e LANG=pl_PL.UTF-8 \
    -v "$RAW":/out -v "$PWD/docs/instrukcja/screenshots.py":/screenshots.py:ro \
    mcr.microsoft.com/playwright/python:v1.49.0-noble \
    bash -c "pip install -q --break-system-packages --disable-pip-version-check --root-user-action=ignore playwright==1.49.0 && python3 /screenshots.py $*" \
    || status=$?

mkdir -p "$IMG"
# Protokoły PDF: pierwsza strona jako obrazek, przycięta do treści.
for pdf in "$RAW"/*.pdf; do
    [[ -e "$pdf" ]] || continue
    name=$(basename "$pdf" .pdf)
    pdftoppm -png -r 110 -f 1 -l 1 -singlefile "$pdf" "$RAW/$name"
    magick "$RAW/$name.png" -trim -bordercolor white -border 24 "$RAW/$name.png"
done
# Zrzuty jako JPEG (kilka razy mniejsze od PNG przy tej samej czytelności).
for png in "$RAW"/*.png; do
    [[ -e "$png" ]] || continue
    magick "$png" -strip -quality 82 "$IMG/$(basename "$png" .png).jpg"
done
ls "$IMG" | wc -l | xargs echo "zrzutów w $IMG:"
exit $status
