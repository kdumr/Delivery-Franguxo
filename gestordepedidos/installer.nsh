!macro customInstall
  ; Checkbox "Iniciar com o Windows" - marcado por padrão
  WriteRegStr HKCU "Software\Microsoft\Windows\CurrentVersion\Run" "FranguxoGestor" "$INSTDIR\Franguxo Gestor de Pedidos.exe"
!macroend

!macro customUnInstall
  ; Remove auto-start ao desinstalar
  DeleteRegValue HKCU "Software\Microsoft\Windows\CurrentVersion\Run" "FranguxoGestor"
!macroend
