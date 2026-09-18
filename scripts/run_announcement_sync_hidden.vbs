' ============================================================================
' run_announcement_sync_hidden.vbs
' ----------------------------------------------------------------------------
' ตัวห่อ (wrapper) สำหรับ Scheduled Task "SIMenu Announcement Register Sync"
'
' เหตุผลที่ต้องมี:
'   Task เดิมเรียก powershell.exe ตรง ๆ ด้วย InteractiveToken ทำให้ Windows
'   สร้าง console window ตั้งแต่ตอน process เกิด (ก่อน -WindowStyle Hidden
'   จะมีผล) หน้าต่างดำจึงเด้งขึ้นมาทุก 5 นาที และ robocopy.exe / php.exe
'   ที่เป็น process ลูกก็ inherit console นั้นต่อ เลยพ่นรายงานออกมาให้เห็น
'
'   wscript.exe ไม่มี console ของตัวเอง และ WshShell.Run(..., 0, True)
'   สั่งให้ process ลูกเกิดแบบ SW_HIDE ตั้งแต่ต้น — ไม่มีหน้าต่างโผล่เลย
'   ส่วน True = รอจนจบ เพื่อให้ Task Scheduler เห็น exit code จริง
'   (MultipleInstances = IgnoreNew จะได้กันรอบทับกันได้ถูกต้อง)
'
' ผลลัพธ์ทั้งหมดถูกเก็บลง log แทนการแสดงบนหน้าจอ
' ============================================================================

Option Explicit

Dim Q, fso, shell
Q = Chr(34)
Set fso = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")

' ── ค่าคงที่ ───────────────────────────────────────────────────────────────
Const LOG_MAX_BYTES = 1048576   ' 1 MB แล้วหมุน log

Dim scriptDir, targetScript, logDir, logFile
scriptDir    = fso.GetParentFolderName(WScript.ScriptFullName)
targetScript = fso.BuildPath(scriptDir, "sync_announcement_register.ps1")
logDir       = "C:\ProgramData\SiMenu"
logFile      = fso.BuildPath(logDir, "announcement_sync.log")

If Not fso.FileExists(targetScript) Then
  WScript.Quit 2
End If

' ── เตรียมที่เก็บ log (นอก webroot เพื่อไม่ให้เปิดผ่านเว็บได้) ─────────────
If Not fso.FolderExists(logDir) Then
  fso.CreateFolder logDir
End If

' หมุน log เมื่อโตเกินกำหนด เก็บไฟล์ก่อนหน้าไว้ 1 รุ่น
If fso.FileExists(logFile) Then
  If fso.GetFile(logFile).Size > LOG_MAX_BYTES Then
    If fso.FileExists(logFile & ".1") Then fso.DeleteFile logFile & ".1", True
    fso.MoveFile logFile, logFile & ".1"
  End If
End If

' ── คั่นหัวรอบด้วย timestamp ───────────────────────────────────────────────
Dim stream
Set stream = fso.OpenTextFile(logFile, 8, True)   ' 8 = ForAppending
stream.WriteLine "===== " & Now & " : announcement register sync ====="
stream.Close

' ── รันแบบซ่อนหน้าต่าง แล้วส่ง stdout/stderr ลง log ────────────────────────
Dim psExe, psArgs, fullCommand, exitCode
psExe  = shell.ExpandEnvironmentStrings("%SystemRoot%") & _
         "\System32\WindowsPowerShell\v1.0\powershell.exe"
psArgs = "-NoProfile -NonInteractive -ExecutionPolicy Bypass -File " & _
         Q & targetScript & Q

fullCommand = "cmd.exe /c " & Q & _
              Q & psExe & Q & " " & psArgs & _
              " >> " & Q & logFile & Q & " 2>&1" & Q

exitCode = shell.Run(fullCommand, 0, True)   ' 0 = SW_HIDE, True = รอจนจบ

WScript.Quit exitCode
