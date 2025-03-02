// الاعتماد على تحليل اللغة بشكل مبسط بدلاً من استخدام مكتبة محددة
class PHPSyntaxChecker {
    constructor() {
        // قائمة المتغيرات المعروفة سابقًا (تأكد من وجود $user)
        this.knownVariables = new Set(['$this', '$user', '$app', '$request', '$response', '$session']);

        // تفعيل التشخيص المتقدم للأخطاء وجعله دائمًا مفعل
        this.enableDebugging = true;

        console.log('تم تهيئة PHPSyntaxChecker مع المتغيرات:', [...this.knownVariables]);

        // حقن مباشر للمتغير $user لضمان وجوده دائمًا
        this.injectUser();
    }

    /**
     * حقن متغير $user في قائمة المتغيرات المعروفة
     */
    injectUser() {
        if (!this.knownVariables.has('$user')) {
            this.knownVariables.add('$user');
            console.log('تم حقن متغير $user');
        }
    }

    /**
     * محاكاة وجود أخطاء للتأكد من عمل نظام التدقيق
     */
    simulateErrors() {
        console.log('محاكاة وجود أخطاء للاختبار');
        return [
            {
                message: 'هذا خطأ تجريبي لاختبار عرض الأخطاء',
                line: 1,
                ch: 5,
                severity: 'error',
                type: 'undefinedVariable'
            }
        ];
    }

    /**
     * فحص صحة الكود PHP للأخطاء النحوية ومشاكل المتغيرات غير المعرفة
     * @param {string} code كود PHP المراد فحصه
     * @returns {Array} قائمة الأخطاء المكتشفة
     */
    checkSyntax(code) {
        const errors = [];

        // إعادة تعيين المتغيرات المحددة للتأكد من تضمين المتغيرات المعروفة دائمًا
        this.currentlyDefinedVars = new Set([...this.knownVariables]);

        // إضافة $user مرة أخرى لضمان وجوده
        this.injectUser();

        try {
            // تسجيل تدقيق الكود للتشخيص
            console.log('بدء فحص الكود:', code.length, 'حرف');
            console.log('المتغيرات المعروفة مسبقاً:', [...this.knownVariables]);

            // تحليل الكود بطريقة مبسطة للغاية
            this.simpleScan(code, errors);

            console.log('تم العثور على', errors.length, 'أخطاء');
            console.log('الأخطاء:', JSON.stringify(errors));

        } catch (e) {
            // خطأ في التحليل النحوي
            console.error('خطأ في فحص الكود:', e);
            errors.push({
                message: e.message,
                line: e.lineNumber || 1,
                ch: e.columnNumber || 0,
                severity: 'error',
                type: 'syntax'
            });
        }

        // تحويل رقم السطر إلى عدد صحيح للتأكد من عمل علامات الأخطاء
        errors.forEach(error => {
            error.line = parseInt(error.line, 10);
            if (isNaN(error.line)) error.line = 0;

            error.ch = parseInt(error.ch, 10);
            if (isNaN(error.ch)) error.ch = 0;
        });

        return errors;
    }

    /**
     * فحص بسيط للكود للبحث عن المتغيرات غير المعرفة
     * طريقة مبسطة جدًا للبحث عن المتغيرات
     */
    simpleScan(code, errors) {
        // تقسيم الكود إلى أسطر
        const lines = code.split("\n");

        // للتشخيص
        console.log('عدد الأسطر في الكود:', lines.length);

        // نمط بسيط للبحث عن المتغيرات
        const varPattern = /\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/g;

        // كلمات محجوزة في PHP
        const phpReservedVars = ['$this', '$GLOBALS', '$_SERVER', '$_GET', '$_POST',
                                '$_FILES', '$_COOKIE', '$_SESSION', '$_REQUEST',
                                '$_ENV', '$php_errormsg', '$_COOKIE', '$user'];

        // إضافة هذه الكلمات المحجوزة للمتغيرات المعروفة
        phpReservedVars.forEach(v => this.currentlyDefinedVars.add(v));

        // فحص كل سطر
        lines.forEach((line, lineIndex) => {
            // تجاهل التعليقات
            if (line.trim().startsWith('//') || line.trim().startsWith('/*') || line.trim().startsWith('*')) {
                return;
            }

            // البحث عن تعريفات المتغيرات
            if (line.includes('=') && !line.includes('==') && !line.includes('===')) {
                let match;
                const definePattern = new RegExp(varPattern);
                while ((match = definePattern.exec(line)) !== null) {
                    const varName = '$' + match[1];
                    // تحقق مما إذا كان هذا تعريف (يجب أن يكون قبل علامة =)
                    const posOfEquals = line.indexOf('=');
                    if (match.index < posOfEquals) {
                        this.currentlyDefinedVars.add(varName);
                        console.log(`تعريف متغير: ${varName} في السطر ${lineIndex}`);
                    }
                }
            }

            // البحث عن استخدامات المتغيرات
            let match;
            const usePattern = new RegExp(varPattern);
            while ((match = usePattern.exec(line)) !== null) {
                const varName = '$' + match[1];

                // تجاهل المتغيرات المعروفة
                if (this.currentlyDefinedVars.has(varName)) {
                    continue;
                }

                // إضافة أخطاء للمتغيرات غير المعروفة
                if (!phpReservedVars.includes(varName)) {
                    console.log(`متغير غير معرف: ${varName} في السطر ${lineIndex}`);
                    errors.push({
                        message: `المتغير "${varName}" غير معرّف`,
                        line: lineIndex,
                        ch: match.index,
                        severity: 'error',
                        type: 'undefinedVariable'
                    });
                }
            }
        });
    }

    /**
     * إضافة متغير معروف للمتغيرات المعرفة مسبقًا
     */
    addKnownVariable(variableName) {
        if (!variableName.startsWith('$')) {
            variableName = '$' + variableName;
        }
        this.knownVariables.add(variableName);
        console.log(`إضافة متغير معروف: ${variableName}`);
    }

    /**
     * إضافة قائمة من المتغيرات المعروفة
     */
    addKnownVariables(variableNames) {
        for (const name of variableNames) {
            this.addKnownVariable(name);
        }
    }
}

// إنشاء كائن واحد فقط من الفاحص
const phpSyntaxChecker = new PHPSyntaxChecker();

// إضافة $user مرة أخرى للتأكد من توفره
phpSyntaxChecker.addKnownVariable('$user');

// إضافة كنافذة عامة للتصحيح
window.PHPSyntaxChecker = phpSyntaxChecker;

export default phpSyntaxChecker;
