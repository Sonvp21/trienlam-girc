#!/bin/bash
cd "${1:-.}" || exit 1

echo "== 1. Route hội thảo AI&IoT =="
grep -rn "hoi-thao-ai-iot" routes/ 2>/dev/null && echo "❌ còn route" || echo "✅ đã xoá"

echo
echo "== 2. File sites-standalone.blade.php =="
[ -f resources/views/sites-standalone.blade.php ] && echo "❌ vẫn còn file" || echo "✅ đã xoá"

echo
echo "== 3. sites.blade.php: 3 mục mới + không trùng lặp =="
F=resources/views/sites.blade.php
for id in tuoi-che-thong-minh bao-cao-thong-ke-nn bac-si-ai-cay-trong; do
  n=$(grep -c "'id' *=> *'$id'" "$F")
  [ "$n" -eq 1 ] && echo "✅ $id (xuất hiện đúng 1 lần)" || echo "❌ $id xuất hiện $n lần"
done
n2=$(grep -c "'id' *=> *'truy-xuat-nguon-goc'" "$F")
[ "$n2" -eq 1 ] && echo "✅ truy-xuat-nguon-goc không còn trùng lặp" || echo "❌ truy-xuat-nguon-goc xuất hiện $n2 lần"

echo
echo "== 4. kiosk.js: ITEM_ICONS đủ 4 key mới =="
JS=public/js/kiosk.js
for id in truy-xuat-nguon-goc tuoi-che-thong-minh bao-cao-thong-ke-nn bac-si-ai-cay-trong; do
  grep -q "'$id':" "$JS" && echo "✅ $id" || echo "❌ THIẾU $id"
done

echo
echo "== 5. kiosk.js: playProject xử lý video_url null an toàn =="
grep -q "if (p.video_url)" "$JS" && echo "✅ đã có guard video_url" || echo "❌ chưa sửa playProject"

echo
echo "== 6. projects.json: 3 project mới =="
PJ=storage/app/projects.json
if command -v jq >/dev/null 2>&1; then
  for id in tuoi-che-thong-minh bao-cao-thong-ke-nn bac-si-ai-cay-trong; do
    jq -e --arg id "$id" '.[] | select(.id==$id)' "$PJ" >/dev/null 2>&1 && echo "✅ $id có trong projects.json" || echo "❌ THIẾU $id trong projects.json"
  done
  jq empty "$PJ" 2>/dev/null && echo "✅ projects.json là JSON hợp lệ" || echo "❌ projects.json LỖI CÚ PHÁP JSON"
else
  for id in tuoi-che-thong-minh bao-cao-thong-ke-nn bac-si-ai-cay-trong; do
    grep -q "\"id\": \"$id\"" "$PJ" && echo "✅ $id có trong projects.json" || echo "❌ THIẾU $id"
  done
fi

echo
echo "== Xong =="